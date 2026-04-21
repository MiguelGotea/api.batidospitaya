' =============================================================
' Módulo: modSyncVentas
' Propósito: Enviar los registros detallados de un pedido
'            a la base de datos MySQL central en tiempo real,
'            sin esperar el proceso batch de Google Sheets.
'
' FUNCIONAMIENTO:
'   1. Recibe el CodPedido y el código de sucursal.
'   2. Ejecuta la misma consulta que genera el CSV de Access.
'   3. Convierte los registros a JSON.
'   4. Llama al API POST /api/sync_ventas_pedido.php
'   5. El API borra los registros previos del pedido e inserta los nuevos.
'
' INSTALACIÓN:
'   1. Abrir Access → Editor VBA (Alt+F11)
'   2. Insertar → Módulo → pegar este código
'   3. Llamar SyncVentasPedido(CodPedido) en los puntos clave:
'      - Al cerrar/imprimir una factura
'      - Al anular un pedido
'      - Al cambiar el estado de entrega (motorizado)
'
' PUNTOS DE LLAMADA SUGERIDOS (en el código de facturación existente):
'   • Form_CerrarFactura → al final del proceso de cierre:
'         SyncVentasPedido Me.CodPedido
'   • Form_AnularPedido  → después de marcar Anulado=True:
'         SyncVentasPedido Me.CodPedido
'   • Form_AsignarMotorizado → al guardar motorizado:
'         SyncVentasPedido Me.CodPedido
' =============================================================

Option Explicit

' (Constantes definidas localmente dentro de cada función para evitar conflictos con otros módulos)

' ══════════════════════════════════════════════════════════
'  FUNCIÓN PÚBLICA PRINCIPAL
'  Llamar desde los formularios de facturación:
'      SyncVentasPedido 38267
'  o   SyncVentasPedido Me.CodPedido
' ══════════════════════════════════════════════════════════
Public Function SyncVentasPedido(lngCodPedido As Long) As Boolean
    On Error GoTo ErrorHandler

    Dim MODO_SILENCIOSO As Boolean : MODO_SILENCIOSO = True  ' False = muestra errores al usuario
    Dim codSuc  As String
    Dim sJson   As String
    Dim sResp   As String
    Dim bOk     As Boolean

    SyncVentasPedido = False

    ' Validar pedido
    If lngCodPedido <= 0 Then
        LogSyncError "SyncVentasPedido", "CodPedido inválido: " & lngCodPedido
        Exit Function
    End If

    ' Obtener código de sucursal local
    codSuc = CStr(codigoLocal())
    If codSuc = "0" Or codSuc = "" Then
        LogSyncError "SyncVentasPedido", "codigoLocal() retornó valor inválido."
        Exit Function
    End If

    ' 1) Construir JSON con los registros del pedido
    sJson = ObtenerRegistrosPedidoJSON(lngCodPedido, codSuc)

    If sJson = "" Then
        LogSyncError "SyncVentasPedido", "No se obtuvieron registros para CodPedido=" & lngCodPedido
        Exit Function
    End If

    ' 2) Enviar al API
    bOk = EnviarSyncAPI(lngCodPedido, codSuc, sJson, sResp)

    SyncVentasPedido = bOk

    If bOk Then
        Debug.Print "[SyncVentas] OK - CodPedido=" & lngCodPedido & " | Resp: " & sResp
    Else
        Debug.Print "[SyncVentas] FALLO - CodPedido=" & lngCodPedido & " | Resp: " & sResp
        If Not MODO_SILENCIOSO Then
            MsgBox "Aviso: No se pudo sincronizar el pedido " & lngCodPedido & " con la nube." & vbCrLf & _
                   "Los datos se actualizarán en el proceso nocturno.", _
                   vbInformation, "Sync Ventas"
        End If
    End If

    Exit Function

ErrorHandler:
    LogSyncError "SyncVentasPedido", "Error " & Err.Number & ": " & Err.Description
    SyncVentasPedido = False
End Function

' ══════════════════════════════════════════════════════════
'  Obtener los registros del pedido desde Access y
'  construir el JSON array para enviar al API.
'
'  Esta es la misma consulta que genera el CSV, adaptada
'  para filtrar por un CodPedido específico.
' ══════════════════════════════════════════════════════════
Private Function ObtenerRegistrosPedidoJSON(lngCodPedido As Long, codSuc As String) As String
    On Error GoTo ErrorHandler

    Dim db      As DAO.Database
    Dim rs      As DAO.Recordset
    Dim sSQL    As String
    Dim sJSON   As String
    Dim sRow    As String
    Dim bPrimero As Boolean

    ' ── SQL con campos RAW únicamente (sin funciones personalizadas en el SELECT) ──────────
    ' Las funciones de usuario (cfechasqlfecha, PrecioReal, OperarioCaja, etc.) causan
    ' Error 5 cuando DAO intenta compilarlas desde contexto de módulo.
    ' Solución: leer campos base y calcular todo en VBA dentro del loop.
    sSQL = "SELECT " & _
           "  NotaDePedido.Anulado, " & _
           "  NotaDePedido.MotivoAnulado, " & _
           "  NotaDePedido.Fecha, " & _
           "  NotaDePedido.Hora, " & _
           "  NotaDePedido.CodPedido, " & _
           "  NotaDePedido.CodCliente, " & _
           "  NotaDePedido.POS, " & _
           "  NotaDePedido.CodMotorizado, " & _
           "  NotaDePedido.Impresiones, " & _
           "  NotaDePedido.Propina, " & _
           "  NotaDePedido.TotalGuardado, " & _
           "  NotaDePedido.HoraCreado, " & _
           "  NotaDePedido.HoraIngresoProducto, " & _
           "  NotaDePedido.HoraImpreso, " & _
           "  SubPedido.CodSubPedido, " & _
           "  SubPedido.CodBatido, " & _
           "  SubPedido.Cantidad, " & _
           "  SubPedido.CodPromocion, " & _
           "  SubPedido.Observaciones, " & _
           "  SubPedido.Puntos, " & _
           "  DBBatidos.Nombre AS DBBatidos_Nombre, " & _
           "  DBBatidos.Medida, " & _
           "  DBBatidos.Precio AS DBBatidos_Precio, "
    sSQL = sSQL & _
           "  Grupos.Tipo, " & _
           "  Grupos.NombreGrupo, " & _
           "  Delivery.Nombre AS Delivery_Nombre " & _
           "FROM ((Delivery INNER JOIN ((SubPedido INNER JOIN DBBatidos ON SubPedido.CodBatido = DBBatidos.CodBatido) " & _
           "  INNER JOIN NotaDePedido ON SubPedido.CodPedido = NotaDePedido.CodPedido) " & _
           "  ON Delivery.CodDelivery = NotaDePedido.Delivery) " & _
           "  INNER JOIN DBPromociones ON SubPedido.CodPromocion = DBPromociones.CodPromocion) " & _
           "  INNER JOIN Grupos ON DBBatidos.CodGrupo = Grupos.CodGrupo " & _
           "WHERE NotaDePedido.CodPedido = " & lngCodPedido

    Debug.Print "[SyncVentas] SQL=" & sSQL

    ' ── Valores calculados UNA vez antes del loop (no dependen de la fila) ───────
    Dim lngPedidoDeCentral As Long
    Dim sSucursalNombre    As String

    On Error Resume Next
    lngPedidoDeCentral = IIf(DCount("*", "StatusPedidosCentral", "[CodPedidoSucursal] = " & lngCodPedido) > 0, 1, 0)
    If Err.Number <> 0 Then lngPedidoDeCentral = 0
    sSucursalNombre = nombrelocalglobal(codSuc)
    If Err.Number <> 0 Then sSucursalNombre = ""
    On Error GoTo ErrorHandler

    Set db = CurrentDb()
    Set rs = db.OpenRecordset(sSQL, dbOpenForwardOnly, dbReadOnly)

    If rs.EOF And rs.BOF Then
        rs.Close
        Set rs = Nothing
        ObtenerRegistrosPedidoJSON = ""
        Exit Function
    End If

    ' ── Construir JSON — funciones de usuario llamadas desde VBA (no desde SQL) ──
    sJSON = "["
    bPrimero = True

    Dim vFecha    As Variant
    Dim vHora     As Variant
    Dim sPrecio   As String
    Dim sPrecUnit As String
    Dim sCaja     As String
    Dim sModal    As String
    Dim sMotor    As String
    Dim sSemana   As String

    Do While Not rs.EOF
        If Not bPrimero Then sJSON = sJSON & ","
        bPrimero = False

        ' ── Todo el bloque bajo Resume Next para aislar cualquier Null ──────────
        On Error Resume Next

        vFecha  = cfechasqlfecha(rs!Fecha)
        If Err.Number <> 0 Then vFecha = rs!Fecha : Err.Clear

        vHora   = cfechasqlhora(rs!Hora)
        If Err.Number <> 0 Then vHora = rs!Hora : Err.Clear

        sPrecio = CStr(CDbl(Nz(rs!Cantidad, 0)) * CDbl(PrecioReal(rs!CodSubPedido)))
        If Err.Number <> 0 Then sPrecio = "0" : Err.Clear

        sCaja   = CStr(OperarioCaja(rs!Hora, rs!Fecha))
        If Err.Number <> 0 Then sCaja = "" : Err.Clear

        sModal  = CStr(statusnotaddepedido(rs!CodPedido))
        If Err.Number <> 0 Then sModal = "" : Err.Clear

        If IsNull(rs!CodMotorizado) Then
            sMotor = ""
        Else
            sMotor = CStr(NombreOperario(rs!CodMotorizado))
            If Err.Number <> 0 Then sMotor = "" : Err.Clear
        End If

        sSemana = CStr(numerosemana(rs!Fecha))
        If Err.Number <> 0 Then sSemana = "null" : Err.Clear

        ' Precio_Unitario_Sin_Descuento: promo 92 y 104 = 0
        Dim nProm As Long : nProm = Nz(rs!CodPromocion, 0)
        If nProm = 92 Or nProm = 104 Then
            sPrecUnit = "0"
        Else
            sPrecUnit = Replace(CStr(CDbl(Nz(rs!DBBatidos_Precio, 0))), ",", ".")
            If Err.Number <> 0 Then sPrecUnit = "null" : Err.Clear
        End If

        ' HoraXxx — se precomputan aquí para evitar Error 94 al llamar cfechasqlhora(Null)
        Dim sHoraCreado          As String
        Dim sHoraIngresoProducto As String
        Dim sHoraImpreso         As String

        sHoraCreado = CStr(cfechasqlhora(rs!HoraCreado))
        If Err.Number <> 0 Then sHoraCreado = "" : Err.Clear

        sHoraIngresoProducto = CStr(cfechasqlhora(rs!HoraIngresoProducto))
        If Err.Number <> 0 Then sHoraIngresoProducto = "" : Err.Clear

        sHoraImpreso = CStr(cfechasqlhora(rs!HoraImpreso))
        If Err.Number <> 0 Then sHoraImpreso = "" : Err.Clear

        ' aPOS — Nz() para manejar POS=Null sin Error 94
        Dim saPOS As String
        saPOS = IIf(Nz(rs!POS, 0) = -1, "A Cuentas", "Efectivo")

        sRow = "{"
        sRow = sRow & """Anulado"":"                        & JSONVal(rs!Anulado) & ","
        sRow = sRow & """MotivoAnulado"":"                  & JSONStr(rs!MotivoAnulado) & ","
        sRow = sRow & """Fecha"":"                          & JSONStr(vFecha) & ","
        sRow = sRow & """Hora"":"                           & JSONStr(vHora) & ","
        sRow = sRow & """CodPedido"":"                      & JSONVal(rs!CodPedido) & ","
        sRow = sRow & """CodCliente"":"                     & JSONVal(rs!CodCliente) & ","
        sRow = sRow & """aPOS"":"                           & JSONStr(saPOS) & ","
        sRow = sRow & """Delivery_Nombre"":"                & JSONStr(rs!Delivery_Nombre) & ","
        sRow = sRow & """Tipo"":"                           & JSONStr(rs!Tipo) & ","
        sRow = sRow & """NombreGrupo"":"                    & JSONStr(rs!NombreGrupo) & ","
        sRow = sRow & """DBBatidos_Nombre"":"               & JSONStr(rs!DBBatidos_Nombre) & ","
        sRow = sRow & """Medida"":"                         & JSONStr(rs!Medida) & ","
        sRow = sRow & """Cantidad"":"                       & JSONVal(rs!Cantidad) & ","
        sRow = sRow & """CodigoPromocion"":"                & JSONVal(rs!CodPromocion) & ","
        sRow = sRow & """Precio"":"                         & sPrecio & ","
        sRow = sRow & """local"":"                          & JSONStr(codSuc) & ","
        sRow = sRow & """Caja"":"                           & JSONStr(sCaja) & ","
        sRow = sRow & """Modalidad"":"                      & JSONStr(sModal) & ","
        sRow = sRow & """Motorizado"":"                     & JSONStr(sMotor) & ","
        sRow = sRow & """Observaciones"":"                  & JSONStr(rs!Observaciones) & ","
        sRow = sRow & """Precio_Unitario_Sin_Descuento"":"  & sPrecUnit & ","
        sRow = sRow & """Impresiones"":"                    & JSONVal(rs!Impresiones) & ","
        sRow = sRow & """HoraCreado"":"                     & JSONStr(sHoraCreado) & ","
        sRow = sRow & """HoraIngresoProducto"":"            & JSONStr(sHoraIngresoProducto) & ","
        sRow = sRow & """HoraImpreso"":"                    & JSONStr(sHoraImpreso) & ","
        sRow = sRow & """Propina"":"                        & JSONVal(rs!Propina) & ","
        sRow = sRow & """Semana"":"                         & sSemana & ","
        sRow = sRow & """Puntos"":"                         & JSONVal(rs!Puntos) & ","
        sRow = sRow & """CodProducto"":"                    & JSONStr(rs!CodBatido) & ","
        sRow = sRow & """MontoFactura"":"                   & JSONVal(rs!TotalGuardado) & ","
        sRow = sRow & """Sucursal_Nombre"":"                & JSONStr(sSucursalNombre) & ","
        sRow = sRow & """PedidoDeCentral"":"                & lngPedidoDeCentral & ","
        sRow = sRow & """CodMotorizado"":"                  & JSONVal(rs!CodMotorizado)
        sRow = sRow & "}"

        On Error GoTo ErrorHandler

        sJSON = sJSON & sRow
        rs.MoveNext
    Loop

    sJSON = sJSON & "]"

    rs.Close
    Set rs = Nothing
    Set db = Nothing

    ObtenerRegistrosPedidoJSON = sJSON
    Exit Function

ErrorHandler:
    LogSyncError "ObtenerRegistrosPedidoJSON", "Error " & Err.Number & ": " & Err.Description
    If Not rs Is Nothing Then rs.Close
    Set rs = Nothing
    Set db = Nothing
    ObtenerRegistrosPedidoJSON = ""
End Function

' ══════════════════════════════════════════════════════════
'  Enviar el payload al API mediante HTTP POST
' ══════════════════════════════════════════════════════════
Private Function EnviarSyncAPI(lngCodPedido As Long, codSuc As String, _
                                sRowsJson As String, ByRef sRespOut As String) As Boolean
    On Error GoTo ErrorHandler

    Dim SYNC_URL       As String  : SYNC_URL       = "https://proxy.batidospitaya.com/api/sync_ventas_pedido.php"
    Dim API_TOKEN      As String  : API_TOKEN      = "a8f5e2d9c4b7a1e6f3d8c5b2a9e6d3f0c7a4b1e8d5c2a9f6e3d0c7b4a1e8f5d2"
    Dim VERSION_MODULO As String  : VERSION_MODULO = "1.0"
    Dim TIMEOUT_MS     As Long    : TIMEOUT_MS     = 15000  ' 15 segundos máximo de espera
    Dim http      As Object
    Dim sBody     As String
    Dim sPayload  As String

    EnviarSyncAPI = False
    sRespOut = ""

    ' Construir body JSON
    ' Enviamos todo como JSON puro en el body (Content-Type: application/json)
    sPayload = "{" & _
               """sucursal"":" & lngCodPedido & "," & _
               """cod_pedido"":" & lngCodPedido & "," & _
               """rows"":" & sRowsJson & _
               "}"

    ' Corregir: sucursal es codSuc, cod_pedido es lngCodPedido
    sPayload = "{" & _
               """sucursal"":""" & codSuc & """," & _
               """cod_pedido"":" & lngCodPedido & "," & _
               """rows"":" & sRowsJson & _
               "}"

    Set http = CreateObject("MSXML2.ServerXMLHTTP.6.0")

    http.Open "POST", SYNC_URL, False
    http.setRequestHeader "Content-Type", "application/json; charset=utf-8"
    http.setRequestHeader "Authorization", "Bearer " & API_TOKEN
    http.setRequestHeader "User-Agent", "PitayaAccess/" & VERSION_MODULO
    http.setTimeouts 5000, 5000, TIMEOUT_MS, TIMEOUT_MS

    http.Send sPayload

    sRespOut = http.responseText

    If http.Status = 200 Then
        ' Verificar que la respuesta diga success:true
        If InStr(sRespOut, """success"":true") > 0 Then
            EnviarSyncAPI = True
        Else
            LogSyncError "EnviarSyncAPI", "API retornó success:false - " & sRespOut
        End If
    Else
        LogSyncError "EnviarSyncAPI", "HTTP " & http.Status & " - " & sRespOut
    End If

    Set http = Nothing
    Exit Function

ErrorHandler:
    LogSyncError "EnviarSyncAPI", "Error " & Err.Number & ": " & Err.Description
    EnviarSyncAPI = False
    Set http = Nothing
End Function

' ══════════════════════════════════════════════════════════
'  Helpers JSON
' ══════════════════════════════════════════════════════════

' Serializar valor numérico o null
Private Function JSONVal(v As Variant) As String
    If IsNull(v) Or IsEmpty(v) Then
        JSONVal = "null"
    ElseIf IsNumeric(v) Then
        ' Usar punto decimal (formato internacional)
        JSONVal = Replace(CStr(CDbl(v)), ",", ".")
    Else
        ' Fallback: tratar como cadena
        JSONVal = """" & JSONEscape(CStr(v)) & """"
    End If
End Function

' Serializar cadena de texto con comillas y escape
Private Function JSONStr(v As Variant) As String
    If IsNull(v) Or IsEmpty(v) Then
        JSONStr = "null"
    Else
        JSONStr = """" & JSONEscape(CStr(v)) & """"
    End If
End Function

' Escape de caracteres especiales JSON
Private Function JSONEscape(s As String) As String
    s = Replace(s, "\", "\\")
    s = Replace(s, """", "\""")
    s = Replace(s, Chr(10), "\n")
    s = Replace(s, Chr(13), "\r")
    s = Replace(s, Chr(9), "\t")
    JSONEscape = s
End Function

' ══════════════════════════════════════════════════════════
'  Log de errores en la tabla de debug de Access
' ══════════════════════════════════════════════════════════
Private Sub LogSyncError(sOrigen As String, sMensaje As String)
    ' Siempre imprimir en la consola de depuración
    Debug.Print "[SyncVentas][" & sOrigen & "] " & sMensaje

    ' Intentar guardar en tabla de log (si existe en el sistema)
    On Error Resume Next
    Dim db As DAO.Database
    Dim rs As DAO.Recordset
    Set db = CurrentDb()
    Set rs = db.OpenRecordset("SELECT * FROM SyncVentasLog WHERE 1=0", dbOpenDynaset)
    If Err.Number = 0 Then
        rs.AddNew
        rs!FechaHora = Now()
        rs!Origen    = Left(sOrigen, 100)
        rs!Mensaje   = Left(sMensaje, 500)
        rs.Update
        rs.Close
    End If
    Set rs = Nothing
    Set db = Nothing
    On Error GoTo 0
End Sub

' ══════════════════════════════════════════════════════════
'  DIAGNÓSTICO — para probar manualmente desde Access
'  Ejecutar: ProbarSyncVentas 38267
' ══════════════════════════════════════════════════════════
Public Sub ProbarSyncVentas(Optional lngCodPedido As Long = 0)
    If lngCodPedido = 0 Then
        Dim sInput As String
        sInput = InputBox("Ingrese el CodPedido a sincronizar:", "Prueba Sync Ventas")
        If sInput = "" Or Not IsNumeric(sInput) Then Exit Sub
        lngCodPedido = CLng(sInput)
    End If

    Dim bOk As Boolean
    bOk = SyncVentasPedido(lngCodPedido)

    If bOk Then
        MsgBox "✅ Sync exitoso para CodPedido=" & lngCodPedido & vbCrLf & _
               "Los registros fueron actualizados en la nube.", _
               vbInformation, "Sync Ventas OK"
    Else
        MsgBox "❌ Error al sincronizar CodPedido=" & lngCodPedido & vbCrLf & _
               "Revise la consola de depuración (Ctrl+G en VBA).", _
               vbExclamation, "Sync Ventas ERROR"
    End If
End Sub
