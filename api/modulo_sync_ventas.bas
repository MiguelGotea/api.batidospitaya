Option Explicit
' =============================================================
' Módulo: modSyncVentas
' Propósito: Enviar los registros de un pedido a MySQL en tiempo real.
'
' USO (agregar en los puntos clave de facturación):
'   SyncVentasPedido Me.CodPedido          → sincroniza un pedido específico
'   SyncVentasDia Date()                   → sincroniza todos los pedidos del día actual
'   SyncVentasDia CDate("2026-07-28")       → sincroniza un día específico
'
' PUNTOS SUGERIDOS:
'   • Al cerrar/imprimir factura
'   • Al anular un pedido
'   • Al asignar motorizado
'
' INSTALACIÓN:
'   Alt+F11 → Archivo → Importar archivo → modulo_sync_ventas.bas
' =============================================================


' ══════════════════════════════════════════════════════════
'  FUNCIÓN PÚBLICA PRINCIPAL
' ══════════════════════════════════════════════════════════
Public Function SyncVentasPedido(lngCodPedido As Long) As Boolean
    On Error GoTo ErrorHandler

    Dim MODO_SILENCIOSO As Boolean : MODO_SILENCIOSO = True
    Dim codSuc As String
    Dim sJson  As String
    Dim sResp  As String
    Dim bOk    As Boolean

    SyncVentasPedido = False

    If lngCodPedido <= 0 Then
        LogSyncError "SyncVentasPedido", "CodPedido invalido: " & lngCodPedido
        Exit Function
    End If

    codSuc = CStr(codigoLocal())
    If codSuc = "0" Or codSuc = "" Then
        LogSyncError "SyncVentasPedido", "codigoLocal() retorno valor invalido."
        Exit Function
    End If

    sJson = ObtenerRegistrosPedidoJSON(lngCodPedido, codSuc)

    If sJson = "" Then
        LogSyncError "SyncVentasPedido", "No se obtuvieron registros para CodPedido=" & lngCodPedido
        Exit Function
    End If

    bOk = EnviarSyncAPI(lngCodPedido, codSuc, sJson, sResp)
    SyncVentasPedido = bOk

    If Not bOk Then
        If Not MODO_SILENCIOSO Then
            MsgBox "Aviso: No se pudo sincronizar el pedido " & lngCodPedido & " con la nube." & vbCrLf & _
                   "Los datos se actualizaran en el proceso nocturno.", vbInformation, "Sync Ventas"
        End If
    End If

    Exit Function
ErrorHandler:
    LogSyncError "SyncVentasPedido", "Error " & Err.Number & ": " & Err.Description
    SyncVentasPedido = False
End Function

' ══════════════════════════════════════════════════════════
'  Leer registros del pedido y construir JSON
'
'  NOTA TÉCNICA: El SQL usa solo campos crudos (sin funciones
'  personalizadas). Las funciones de usuario causan Error 5
'  cuando DAO las compila desde contexto de módulo.
'  Todas las funciones se calculan en VBA dentro del loop.
' ══════════════════════════════════════════════════════════
Private Function ObtenerRegistrosPedidoJSON(lngCodPedido As Long, codSuc As String) As String
    On Error GoTo ErrorHandler

    Dim db       As DAO.Database
    Dim rs       As DAO.Recordset
    Dim sSQL     As String
    Dim sJSON    As String
    Dim sRow     As String
    Dim bPrimero As Boolean

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

    ' ── Valores calculados una vez, fuera del loop ───────────────────────────
    Dim lngPedidoDeCentral As Long
    Dim sSucursalNombre    As String

    On Error Resume Next
    lngPedidoDeCentral = IIf(DCount("*", "StatusPedidosCentral", "[CodPedidoSucursal] = " & lngCodPedido) > 0, 1, 0)
    If Err.Number <> 0 Then lngPedidoDeCentral = 0 : Err.Clear
    sSucursalNombre = nombrelocalglobal(CInt(codSuc))
    If Err.Number <> 0 Then sSucursalNombre = "" : Err.Clear
    On Error GoTo ErrorHandler

    Set db = CurrentDb()
    Set rs = db.OpenRecordset(sSQL, dbOpenForwardOnly, dbReadOnly)

    If rs.EOF And rs.BOF Then
        rs.Close : Set rs = Nothing
        ObtenerRegistrosPedidoJSON = ""
        Exit Function
    End If

    ' ── Construir JSON ───────────────────────────────────────────────────────
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
    Dim sHoraC    As String
    Dim sHoraI    As String
    Dim sHoraIm   As String
    Dim saPOS     As String
    Dim nProm     As Long

    Do While Not rs.EOF
        If Not bPrimero Then sJSON = sJSON & ","
        bPrimero = False

        ' Todos los cálculos bajo Resume Next para aislar Null y errores de funciones
        On Error Resume Next

        vFecha = cfechasqlfecha(rs!Fecha)
        If Err.Number <> 0 Then vFecha = rs!Fecha : Err.Clear

        vHora = cfechasqlhora(rs!Hora)
        If Err.Number <> 0 Then vHora = rs!Hora : Err.Clear

        sPrecio = CStr(CDbl(Nz(rs!Cantidad, 0)) * CDbl(PrecioReal(rs!CodSubPedido)))
        If Err.Number <> 0 Then sPrecio = "0" : Err.Clear

        sCaja = CStr(OperarioCaja(rs!Hora, rs!Fecha))
        If Err.Number <> 0 Then sCaja = "" : Err.Clear

        sModal = CStr(statusnotaddepedido(rs!CodPedido))
        If Err.Number <> 0 Then sModal = "" : Err.Clear

        If IsNull(rs!CodMotorizado) Then
            sMotor = ""
        Else
            sMotor = CStr(NombreOperario(rs!CodMotorizado))
            If Err.Number <> 0 Then sMotor = "" : Err.Clear
        End If

        sSemana = CStr(numerosemana(rs!Fecha))
        If Err.Number <> 0 Then sSemana = "null" : Err.Clear

        nProm = Nz(rs!CodPromocion, 0)
        If nProm = 92 Or nProm = 104 Then
            sPrecUnit = "0"
        Else
            sPrecUnit = Replace(CStr(CDbl(Nz(rs!DBBatidos_Precio, 0))), ",", ".")
            If Err.Number <> 0 Then sPrecUnit = "null" : Err.Clear
        End If

        sHoraC = CStr(cfechasqlhora(rs!HoraCreado))
        If Err.Number <> 0 Then sHoraC = "" : Err.Clear

        sHoraI = CStr(cfechasqlhora(rs!HoraIngresoProducto))
        If Err.Number <> 0 Then sHoraI = "" : Err.Clear

        sHoraIm = CStr(cfechasqlhora(rs!HoraImpreso))
        If Err.Number <> 0 Then sHoraIm = "" : Err.Clear

        saPOS = IIf(Nz(rs!POS, 0) = -1, "A Cuentas", "Efectivo")

        sRow = "{"
        sRow = sRow & """Anulado"":"                       & JSONVal(rs!Anulado) & ","
        sRow = sRow & """MotivoAnulado"":"                 & JSONStr(rs!MotivoAnulado) & ","
        sRow = sRow & """Fecha"":"                         & JSONStr(vFecha) & ","
        sRow = sRow & """Hora"":"                          & JSONStr(vHora) & ","
        sRow = sRow & """CodPedido"":"                     & JSONVal(rs!CodPedido) & ","
        sRow = sRow & """CodCliente"":"                    & JSONVal(rs!CodCliente) & ","
        sRow = sRow & """aPOS"":"                          & JSONStr(saPOS) & ","
        sRow = sRow & """Delivery_Nombre"":"               & JSONStr(rs!Delivery_Nombre) & ","
        sRow = sRow & """Tipo"":"                          & JSONStr(rs!Tipo) & ","
        sRow = sRow & """NombreGrupo"":"                   & JSONStr(rs!NombreGrupo) & ","
        sRow = sRow & """DBBatidos_Nombre"":"              & JSONStr(rs!DBBatidos_Nombre) & ","
        sRow = sRow & """Medida"":"                        & JSONStr(rs!Medida) & ","
        sRow = sRow & """Cantidad"":"                      & JSONVal(rs!Cantidad) & ","
        sRow = sRow & """CodigoPromocion"":"               & JSONVal(rs!CodPromocion) & ","
        sRow = sRow & """Precio"":"                        & sPrecio & ","
        sRow = sRow & """local"":"                         & JSONStr(codSuc) & ","
        sRow = sRow & """Caja"":"                          & JSONStr(sCaja) & ","
        sRow = sRow & """Modalidad"":"                     & JSONStr(sModal) & ","
        sRow = sRow & """Motorizado"":"                    & JSONStr(sMotor) & ","
        sRow = sRow & """Observaciones"":"                 & JSONStr(rs!Observaciones) & ","
        sRow = sRow & """Precio_Unitario_Sin_Descuento"":" & sPrecUnit & ","
        sRow = sRow & """Impresiones"":"                   & JSONVal(rs!Impresiones) & ","
        sRow = sRow & """HoraCreado"":"                    & JSONStr(sHoraC) & ","
        sRow = sRow & """HoraIngresoProducto"":"           & JSONStr(sHoraI) & ","
        sRow = sRow & """HoraImpreso"":"                   & JSONStr(sHoraIm) & ","
        sRow = sRow & """Propina"":"                       & JSONVal(rs!Propina) & ","
        sRow = sRow & """Semana"":"                        & sSemana & ","
        sRow = sRow & """Puntos"":"                        & JSONVal(rs!Puntos) & ","
        sRow = sRow & """CodProducto"":"                   & JSONStr(rs!CodBatido) & ","
        sRow = sRow & """MontoFactura"":"                  & JSONVal(rs!TotalGuardado) & ","
        sRow = sRow & """Sucursal_Nombre"":"               & JSONStr(sSucursalNombre) & ","
        sRow = sRow & """PedidoDeCentral"":"               & lngPedidoDeCentral & ","
        sRow = sRow & """CodMotorizado"":"                 & JSONVal(rs!CodMotorizado)
        sRow = sRow & "}"

        On Error GoTo ErrorHandler
        sJSON = sJSON & sRow
        rs.MoveNext
    Loop

    sJSON = sJSON & "]"
    rs.Close : Set rs = Nothing : Set db = Nothing
    ObtenerRegistrosPedidoJSON = sJSON
    Exit Function

ErrorHandler:
    LogSyncError "ObtenerRegistrosPedidoJSON", "Error " & Err.Number & ": " & Err.Description
    If Not rs Is Nothing Then rs.Close
    Set rs = Nothing : Set db = Nothing
    ObtenerRegistrosPedidoJSON = ""
End Function

' ══════════════════════════════════════════════════════════
'  HTTP POST al API
' ══════════════════════════════════════════════════════════
Private Function EnviarSyncAPI(lngCodPedido As Long, codSuc As String, _
                                sRowsJson As String, ByRef sRespOut As String) As Boolean
    On Error GoTo ErrorHandler

    Dim SYNC_URL  As String : SYNC_URL  = "https://api.batidospitaya.com/api/sync_ventas_pedido.php"
    Dim API_TOKEN As String : API_TOKEN = "a8f5e2d9c4b7a1e6f3d8c5b2a9e6d3f0c7a4b1e8d5c2a9f6e3d0c7b4a1e8f5d2"
    Dim TIMEOUT   As Long   : TIMEOUT   = 15000

    Dim http     As Object
    Dim sPayload As String

    EnviarSyncAPI = False
    sRespOut = ""

    sPayload = "{" & _
               """sucursal"":""" & codSuc & """," & _
               """cod_pedido"":" & lngCodPedido & "," & _
               """rows"":" & sRowsJson & _
               "}"

    Set http = CreateObject("MSXML2.ServerXMLHTTP.6.0")
    http.Open "POST", SYNC_URL, False
    http.setRequestHeader "Content-Type", "application/json; charset=utf-8"
    http.setRequestHeader "Authorization", "Bearer " & API_TOKEN
    http.setRequestHeader "User-Agent", "PitayaAccess/1.0"
    http.setTimeouts 5000, 5000, TIMEOUT, TIMEOUT
    http.Send sPayload

    sRespOut = http.responseText

    If http.Status = 200 Then
        If InStr(sRespOut, """success"":true") > 0 Then
            EnviarSyncAPI = True
        Else
            LogSyncError "EnviarSyncAPI", "API error - " & sRespOut
        End If
    Else
        LogSyncError "EnviarSyncAPI", "HTTP " & http.Status & " - " & sRespOut
    End If

    Set http = Nothing
    Exit Function
ErrorHandler:
    LogSyncError "EnviarSyncAPI", "Error " & Err.Number & ": " & Err.Description
    Set http = Nothing
End Function

' ══════════════════════════════════════════════════════════
'  Helpers JSON
' ══════════════════════════════════════════════════════════
Private Function JSONVal(v As Variant) As String
    If IsNull(v) Or IsEmpty(v) Then
        JSONVal = "null"
    ElseIf IsNumeric(v) Then
        JSONVal = Replace(CStr(CDbl(v)), ",", ".")
    Else
        JSONVal = """" & JSONEscape(CStr(v)) & """"
    End If
End Function

Private Function JSONStr(v As Variant) As String
    If IsNull(v) Or IsEmpty(v) Then
        JSONStr = "null"
    Else
        JSONStr = """" & JSONEscape(CStr(v)) & """"
    End If
End Function

Private Function JSONEscape(s As String) As String
    s = Replace(s, "\", "\\")
    s = Replace(s, """", "\""")
    s = Replace(s, Chr(10), "\n")
    s = Replace(s, Chr(13), "\r")
    s = Replace(s, Chr(9), "\t")
    JSONEscape = s
End Function

' ══════════════════════════════════════════════════════════
'  Log de errores (consola + tabla SyncVentasLog si existe)
' ══════════════════════════════════════════════════════════
Private Sub LogSyncError(sOrigen As String, sMensaje As String)
    Debug.Print "[SyncVentas][" & sOrigen & "] " & sMensaje

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
    Set rs = Nothing : Set db = Nothing
    On Error GoTo 0
End Sub

' ══════════════════════════════════════════════════════════
'  Diagnóstico manual: ProbarSyncVentas 4530
' ══════════════════════════════════════════════════════════
Public Sub ProbarSyncVentas(Optional lngCodPedido As Long = 0)
    If lngCodPedido = 0 Then
        Dim sInput As String
        sInput = InputBox("Ingrese el CodPedido a sincronizar:", "Prueba Sync Ventas")
        If sInput = "" Or Not IsNumeric(sInput) Then Exit Sub
        lngCodPedido = CLng(sInput)
    End If

    If SyncVentasPedido(lngCodPedido) Then
        MsgBox "OK - Pedido " & lngCodPedido & " sincronizado.", vbInformation, "Sync Ventas"
    Else
        MsgBox "ERROR - Pedido " & lngCodPedido & vbCrLf & "Ver Ctrl+G en VBA.", vbExclamation, "Sync Ventas"
    End If
End Sub

' ══════════════════════════════════════════════════════════
'  SYNC DÍA COMPLETO — Función pública
'
'  Envía todos los pedidos de una fecha al API en lotes (chunks).
'  Uso:
'    SyncVentasDia Date()                  → día actual
'    SyncVentasDia CDate("2026-07-28")     → día específico
'    SyncVentasDia DateAdd("d",-1,Date())  → ayer
' ══════════════════════════════════════════════════════════
Public Function SyncVentasDia(dtFecha As Date) As Boolean
    On Error GoTo ErrorHandler

    Const CHUNK_SIZE As Long = 40   ' filas por POST (ajustar si hay timeout)

    Dim codSuc     As String
    Dim sFecha     As String
    Dim colRows    As Collection
    Dim lngTotal   As Long
    Dim lngChunks  As Long
    Dim i          As Long
    Dim j          As Long
    Dim lngInicio  As Long
    Dim lngFin     As Long
    Dim sChunkJson As String
    Dim bPrimero   As Boolean
    Dim sResp      As String
    Dim bOk        As Boolean

    SyncVentasDia = False

    If dtFecha = 0 Then
        LogSyncError "SyncVentasDia", "Fecha invalida proporcionada."
        Exit Function
    End If

    codSuc = CStr(codigoLocal())
    If codSuc = "0" Or codSuc = "" Then
        LogSyncError "SyncVentasDia", "codigoLocal() retorno valor invalido."
        Exit Function
    End If

    sFecha = Format(dtFecha, "YYYY-MM-DD")

    ' Obtener todas las filas del día como colección de strings JSON
    Set colRows = ObtenerFilasDiaColeccion(dtFecha, codSuc)

    If colRows Is Nothing Then
        LogSyncError "SyncVentasDia", "Error al obtener filas para fecha=" & sFecha
        Exit Function
    End If

    lngTotal = colRows.Count
    If lngTotal = 0 Then
        LogSyncError "SyncVentasDia", "Sin registros para fecha=" & sFecha
        Exit Function
    End If

    ' Calcular número de chunks necesarios
    lngChunks = Int((lngTotal - 1) / CHUNK_SIZE) + 1

    For i = 1 To lngChunks
        lngInicio = (i - 1) * CHUNK_SIZE + 1
        lngFin    = lngInicio + CHUNK_SIZE - 1
        If lngFin > lngTotal Then lngFin = lngTotal

        ' Construir JSON array del chunk actual
        sChunkJson = "["
        bPrimero = True
        For j = lngInicio To lngFin
            If Not bPrimero Then sChunkJson = sChunkJson & ","
            bPrimero = False
            sChunkJson = sChunkJson & colRows(j)
        Next j
        sChunkJson = sChunkJson & "]"

        bOk = EnviarSyncAPIDia(sFecha, codSuc, sChunkJson, (i = 1), i, lngChunks, sResp)

        If Not bOk Then
            LogSyncError "SyncVentasDia", _
                "Fallo chunk " & i & "/" & lngChunks & " | fecha=" & sFecha & _
                " | resp=" & Left(sResp, 200)
            Exit Function
        End If

        DoEvents  ' Permite que Access respire entre chunks
    Next i

    SyncVentasDia = True
    Exit Function

ErrorHandler:
    LogSyncError "SyncVentasDia", "Error " & Err.Number & ": " & Err.Description
    SyncVentasDia = False
End Function

' ══════════════════════════════════════════════════════════
'  Leer TODOS los registros del día y construir colección de strings JSON
'
'  NOTA: El resultado se almacena en una Collection para poder
'  iterar y dividir en chunks sin reabrir el Recordset.
'  La clave PedidoDeCentral se cachea por CodPedido para evitar
'  un DCount redundante por cada sub-línea del mismo pedido.
' ══════════════════════════════════════════════════════════
Private Function ObtenerFilasDiaColeccion(dtFecha As Date, codSuc As String) As Collection
    On Error GoTo ErrorHandler

    Dim db  As DAO.Database
    Dim rs  As DAO.Recordset
    Dim col As New Collection
    Dim sSQL As String

    ' Filtro por rango de fechas (>= hoy, < mañana) — compatible con Jet/ACE
    Dim dFechaFin As Date
    dFechaFin = DateAdd("d", 1, dtFecha)

    Dim sFrom As String : sFrom = Format(dtFecha,  "MM/DD/YYYY")
    Dim sTo   As String : sTo   = Format(dFechaFin, "MM/DD/YYYY")

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
           "WHERE NotaDePedido.Fecha >= #" & sFrom & "# AND NotaDePedido.Fecha < #" & sTo & "# " & _
           "ORDER BY NotaDePedido.CodPedido"

    ' ── Sucursal nombre (calculado una sola vez) ─────────────────────────────
    Dim sSucursalNombre As String
    On Error Resume Next
    sSucursalNombre = nombrelocalglobal(CInt(codSuc))
    If Err.Number <> 0 Then sSucursalNombre = "" : Err.Clear
    On Error GoTo ErrorHandler

    Set db = CurrentDb()
    Set rs = db.OpenRecordset(sSQL, dbOpenForwardOnly, dbReadOnly)

    If rs.EOF And rs.BOF Then
        rs.Close : Set rs = Nothing : Set db = Nothing
        Set ObtenerFilasDiaColeccion = col  ' Colección vacía (sin registros)
        Exit Function
    End If

    ' ── Variables de loop ────────────────────────────────────────────────────
    Dim vFecha    As Variant
    Dim vHora     As Variant
    Dim sPrecio   As String
    Dim sPrecUnit As String
    Dim sCaja     As String
    Dim sModal    As String
    Dim sMotor    As String
    Dim sSemana   As String
    Dim sHoraC    As String
    Dim sHoraI    As String
    Dim sHoraIm   As String
    Dim saPOS     As String
    Dim nProm     As Long
    Dim sRow      As String

    ' Cache de PedidoDeCentral: evita DCount repetido para sub-líneas del mismo pedido
    Dim lngUltimoPedido    As Long : lngUltimoPedido    = -1
    Dim lngPedidoDeCentral As Long : lngPedidoDeCentral = 0

    Do While Not rs.EOF
        On Error Resume Next

        ' ── Actualizar cache cuando cambia el CodPedido ──────────────────────
        If CLng(Nz(rs!CodPedido, 0)) <> lngUltimoPedido Then
            lngUltimoPedido = CLng(Nz(rs!CodPedido, 0))
            lngPedidoDeCentral = IIf( _
                DCount("*", "StatusPedidosCentral", "[CodPedidoSucursal] = " & lngUltimoPedido) > 0, _
                1, 0)
            If Err.Number <> 0 Then lngPedidoDeCentral = 0 : Err.Clear
        End If

        vFecha = cfechasqlfecha(rs!Fecha)
        If Err.Number <> 0 Then vFecha = rs!Fecha : Err.Clear

        vHora = cfechasqlhora(rs!Hora)
        If Err.Number <> 0 Then vHora = rs!Hora : Err.Clear

        sPrecio = CStr(CDbl(Nz(rs!Cantidad, 0)) * CDbl(PrecioReal(rs!CodSubPedido)))
        If Err.Number <> 0 Then sPrecio = "0" : Err.Clear

        sCaja = CStr(OperarioCaja(rs!Hora, rs!Fecha))
        If Err.Number <> 0 Then sCaja = "" : Err.Clear

        sModal = CStr(statusnotaddepedido(rs!CodPedido))
        If Err.Number <> 0 Then sModal = "" : Err.Clear

        If IsNull(rs!CodMotorizado) Then
            sMotor = ""
        Else
            sMotor = CStr(NombreOperario(rs!CodMotorizado))
            If Err.Number <> 0 Then sMotor = "" : Err.Clear
        End If

        sSemana = CStr(numerosemana(rs!Fecha))
        If Err.Number <> 0 Then sSemana = "null" : Err.Clear

        nProm = Nz(rs!CodPromocion, 0)
        If nProm = 92 Or nProm = 104 Then
            sPrecUnit = "0"
        Else
            sPrecUnit = Replace(CStr(CDbl(Nz(rs!DBBatidos_Precio, 0))), ",", ".")
            If Err.Number <> 0 Then sPrecUnit = "null" : Err.Clear
        End If

        sHoraC  = CStr(cfechasqlhora(rs!HoraCreado))
        If Err.Number <> 0 Then sHoraC = "" : Err.Clear

        sHoraI  = CStr(cfechasqlhora(rs!HoraIngresoProducto))
        If Err.Number <> 0 Then sHoraI = "" : Err.Clear

        sHoraIm = CStr(cfechasqlhora(rs!HoraImpreso))
        If Err.Number <> 0 Then sHoraIm = "" : Err.Clear

        saPOS = IIf(Nz(rs!POS, 0) = -1, "A Cuentas", "Efectivo")

        sRow = "{"
        sRow = sRow & """Anulado"":"                       & JSONVal(rs!Anulado) & ","
        sRow = sRow & """MotivoAnulado"":"                 & JSONStr(rs!MotivoAnulado) & ","
        sRow = sRow & """Fecha"":"                         & JSONStr(vFecha) & ","
        sRow = sRow & """Hora"":"                          & JSONStr(vHora) & ","
        sRow = sRow & """CodPedido"":"                     & JSONVal(rs!CodPedido) & ","
        sRow = sRow & """CodCliente"":"                    & JSONVal(rs!CodCliente) & ","
        sRow = sRow & """aPOS"":"                          & JSONStr(saPOS) & ","
        sRow = sRow & """Delivery_Nombre"":"               & JSONStr(rs!Delivery_Nombre) & ","
        sRow = sRow & """Tipo"":"                          & JSONStr(rs!Tipo) & ","
        sRow = sRow & """NombreGrupo"":"                   & JSONStr(rs!NombreGrupo) & ","
        sRow = sRow & """DBBatidos_Nombre"":"              & JSONStr(rs!DBBatidos_Nombre) & ","
        sRow = sRow & """Medida"":"                        & JSONStr(rs!Medida) & ","
        sRow = sRow & """Cantidad"":"                      & JSONVal(rs!Cantidad) & ","
        sRow = sRow & """CodigoPromocion"":"               & JSONVal(rs!CodPromocion) & ","
        sRow = sRow & """Precio"":"                        & sPrecio & ","
        sRow = sRow & """local"":"                         & JSONStr(codSuc) & ","
        sRow = sRow & """Caja"":"                          & JSONStr(sCaja) & ","
        sRow = sRow & """Modalidad"":"                     & JSONStr(sModal) & ","
        sRow = sRow & """Motorizado"":"                    & JSONStr(sMotor) & ","
        sRow = sRow & """Observaciones"":"                 & JSONStr(rs!Observaciones) & ","
        sRow = sRow & """Precio_Unitario_Sin_Descuento"":" & sPrecUnit & ","
        sRow = sRow & """Impresiones"":"                   & JSONVal(rs!Impresiones) & ","
        sRow = sRow & """HoraCreado"":"                    & JSONStr(sHoraC) & ","
        sRow = sRow & """HoraIngresoProducto"":"           & JSONStr(sHoraI) & ","
        sRow = sRow & """HoraImpreso"":"                   & JSONStr(sHoraIm) & ","
        sRow = sRow & """Propina"":"                       & JSONVal(rs!Propina) & ","
        sRow = sRow & """Semana"":"                        & sSemana & ","
        sRow = sRow & """Puntos"":"                        & JSONVal(rs!Puntos) & ","
        sRow = sRow & """CodProducto"":"                   & JSONStr(rs!CodBatido) & ","
        sRow = sRow & """MontoFactura"":"                  & JSONVal(rs!TotalGuardado) & ","
        sRow = sRow & """Sucursal_Nombre"":"               & JSONStr(sSucursalNombre) & ","
        sRow = sRow & """PedidoDeCentral"":"               & lngPedidoDeCentral & ","
        sRow = sRow & """CodMotorizado"":"                 & JSONVal(rs!CodMotorizado)
        sRow = sRow & "}"

        On Error GoTo ErrorHandler
        col.Add sRow
        rs.MoveNext
    Loop

    rs.Close : Set rs = Nothing : Set db = Nothing
    Set ObtenerFilasDiaColeccion = col
    Exit Function

ErrorHandler:
    LogSyncError "ObtenerFilasDiaColeccion", "Error " & Err.Number & ": " & Err.Description
    If Not rs Is Nothing Then rs.Close
    Set rs = Nothing : Set db = Nothing
    Set ObtenerFilasDiaColeccion = Nothing
End Function

' ══════════════════════════════════════════════════════════
'  HTTP POST al API — Sync por día (con soporte de chunks)
'
'  bEsPrimerChunk = True  → el PHP ejecuta DELETE antes de INSERT
'  bEsPrimerChunk = False → el PHP solo ejecuta INSERT (acumula)
' ══════════════════════════════════════════════════════════
Private Function EnviarSyncAPIDia(sFecha As String, codSuc As String, _
                                   sRowsJson As String, bEsPrimerChunk As Boolean, _
                                   lngChunkIdx As Long, lngTotalChunks As Long, _
                                   ByRef sRespOut As String) As Boolean
    On Error GoTo ErrorHandler

    Dim SYNC_URL  As String : SYNC_URL  = "https://api.batidospitaya.com/api/sync_ventas_dia.php"
    Dim API_TOKEN As String : API_TOKEN = "a8f5e2d9c4b7a1e6f3d8c5b2a9e6d3f0c7a4b1e8d5c2a9f6e3d0c7b4a1e8f5d2"
    Dim TIMEOUT   As Long   : TIMEOUT   = 20000  ' 20s — más holgura que el sync por pedido

    Dim http     As Object
    Dim sPayload As String

    EnviarSyncAPIDia = False
    sRespOut = ""

    sPayload = "{" & _
               """sucursal"":""" & codSuc & """," & _
               """fecha"":""" & sFecha & """," & _
               """chunk_index"":" & lngChunkIdx & "," & _
               """total_chunks"":" & lngTotalChunks & "," & _
               """is_first_chunk"":" & IIf(bEsPrimerChunk, "true", "false") & "," & _
               """rows"":" & sRowsJson & _
               "}"

    Set http = CreateObject("MSXML2.ServerXMLHTTP.6.0")
    http.Open "POST", SYNC_URL, False
    http.setRequestHeader "Content-Type", "application/json; charset=utf-8"
    http.setRequestHeader "Authorization", "Bearer " & API_TOKEN
    http.setRequestHeader "User-Agent", "PitayaAccess/1.0"
    http.setTimeouts 5000, 5000, TIMEOUT, TIMEOUT
    http.Send sPayload

    sRespOut = http.responseText

    If http.Status = 200 Then
        If InStr(sRespOut, """success"":true") > 0 Then
            EnviarSyncAPIDia = True
        Else
            LogSyncError "EnviarSyncAPIDia", _
                "API error chunk " & lngChunkIdx & "/" & lngTotalChunks & " - " & sRespOut
        End If
    Else
        LogSyncError "EnviarSyncAPIDia", _
            "HTTP " & http.Status & " chunk " & lngChunkIdx & "/" & lngTotalChunks & " - " & sRespOut
    End If

    Set http = Nothing
    Exit Function
ErrorHandler:
    LogSyncError "EnviarSyncAPIDia", "Error " & Err.Number & ": " & Err.Description
    Set http = Nothing
End Function

' ══════════════════════════════════════════════════════════
'  Diagnóstico manual:
'    ProbarSyncVentasDia               → InputBox para ingresar fecha
'    ProbarSyncVentasDia #7/28/2026#   → fecha directa
' ══════════════════════════════════════════════════════════
Public Sub ProbarSyncVentasDia(Optional dtFecha As Variant)
    Dim dFecha As Date

    If IsMissing(dtFecha) Or IsEmpty(dtFecha) Then
        Dim sInput As String
        sInput = InputBox("Ingrese la fecha a sincronizar (YYYY-MM-DD):", "Prueba Sync Ventas Día", Format(Date, "YYYY-MM-DD"))
        If sInput = "" Then Exit Sub
        If Not IsDate(sInput) Then
            MsgBox "Fecha inválida: " & sInput, vbExclamation, "Sync Ventas Día"
            Exit Sub
        End If
        dFecha = CDate(sInput)
    Else
        If Not IsDate(dtFecha) Then
            MsgBox "Fecha inválida proporcionada.", vbExclamation, "Sync Ventas Día"
            Exit Sub
        End If
        dFecha = CDate(dtFecha)
    End If

    If SyncVentasDia(dFecha) Then
        MsgBox "OK - Día " & Format(dFecha, "YYYY-MM-DD") & " sincronizado.", _
               vbInformation, "Sync Ventas Día"
    Else
        MsgBox "ERROR - Día " & Format(dFecha, "YYYY-MM-DD") & vbCrLf & _
               "Ver Ctrl+G en VBA.", vbExclamation, "Sync Ventas Día"
    End If
End Sub
