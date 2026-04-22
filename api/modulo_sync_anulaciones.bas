' =============================================================
' Módulo: modSyncAnulaciones
' Propósito: Sincronización bidireccional de AnulacionPedidos
'            entre Access y el host MySQL.
'
' FLUJO COMPLETO:
'   1. SyncEnviarAnulacionesPendientes()
'      → Lee AnulacionPedidos WHERE Status=0 y los sube al host.
'      → Llamar al guardar/crear una solicitud de anulación.
'
'   2. Timer (cada 60 seg) → SyncLeerRespuestasAnulacion()
'      → Consulta el host con los CodPedido en Status=0 locales.
'      → Si el host los tiene aprobados: ejecuta anulación en Access,
'        marca Status=1 local y manda confirmación al host.
'
'   3. SyncMasivoHistorialAnulaciones()
'      → Sube TODOS los registros de AnulacionPedidos (historial completo).
'      → Uso puntual / migración inicial.
'
' INSTALACIÓN:
'   Alt+F11 → Archivo → Importar → modulo_sync_anulaciones.bas
' =============================================================


' ── (Configuración movida a variables locales en cada función para evitar
'     colisiones de nombres con otros módulos del proyecto) ────────────────────

' ══════════════════════════════════════════════════════════
' 1. ENVIAR solicitudes pendientes al host
'    → Llamar al crear/guardar cada solicitud de anulación
' ══════════════════════════════════════════════════════════
Public Function SyncEnviarAnulacionesPendientes() As Boolean
    On Error GoTo ErrorHandler

    Dim sAnulUrlEnviar As String
    sAnulUrlEnviar = "https://proxy.batidospitaya.com/api/sync_anulacion_pedidos.php"

    Dim codSuc As String
    codSuc = CStr(codigoLocal())
    If codSuc = "0" Or codSuc = "" Then
        AnulacionLog "SyncEnviar", "codigoLocal() inválido."
        Exit Function
    End If

    Dim db  As DAO.Database
    Dim rs  As DAO.Recordset
    Set db = CurrentDb()

    ' Solo Status=0 (pendientes de resolución)
    Set rs = db.OpenRecordset( _
        "SELECT * FROM AnulacionPedidos WHERE Status = 0", _
        dbOpenForwardOnly, dbReadOnly)

    If rs.EOF And rs.BOF Then
        rs.Close : Set rs = Nothing : Set db = Nothing
        SyncEnviarAnulacionesPendientes = True
        Exit Function
    End If

    Dim sJson  As String
    Dim sResp  As String
    sJson = BuildAnulacionesJSON(rs, codSuc)
    rs.Close : Set rs = Nothing : Set db = Nothing

    If sJson = "[]" Or sJson = "" Then
        SyncEnviarAnulacionesPendientes = True
        Exit Function
    End If

    Dim sPayload As String
    sPayload = "{""sucursal"":""" & codSuc & """,""rows"":" & sJson & "}"

    Dim bOk As Boolean
    bOk = HttpPost(sAnulUrlEnviar, sPayload, sResp)
    SyncEnviarAnulacionesPendientes = bOk

    If Not bOk Then
        AnulacionLog "SyncEnviar", "Fallo HTTP: " & sResp
    Else
        AnulacionLog "SyncEnviar", "OK - " & sResp
    End If

    Exit Function
ErrorHandler:
    AnulacionLog "SyncEnviar", "Error " & Err.Number & ": " & Err.Description
    SyncEnviarAnulacionesPendientes = False
End Function

' ══════════════════════════════════════════════════════════
' 2. LEER respuestas del host y ejecutar anulaciones locales
'    → Llamar en un Timer cada 60 segundos
' ══════════════════════════════════════════════════════════
Public Function SyncLeerRespuestasAnulacion() As Boolean
    On Error GoTo ErrorHandler

    Dim sAnulUrlLeer As String
    sAnulUrlLeer = "https://proxy.batidospitaya.com/api/read_anulacion_pedidos.php"

    Dim codSuc As String
    codSuc = CStr(codigoLocal())
    If codSuc = "0" Or codSuc = "" Then
        AnulacionLog "SyncLeer", "codigoLocal() inválido."
        Exit Function
    End If

    Dim db  As DAO.Database
    Dim rs  As DAO.Recordset
    Set db = CurrentDb()

    ' Obtener CodPedidos con Status=0 local
    Set rs = db.OpenRecordset( _
        "SELECT CodPedido FROM AnulacionPedidos WHERE Status = 0", _
        dbOpenForwardOnly, dbReadOnly)

    If rs.EOF And rs.BOF Then
        rs.Close : Set rs = Nothing : Set db = Nothing
        SyncLeerRespuestasAnulacion = True
        Exit Function
    End If

    ' Construir array de CodPedidos
    Dim sCodigos As String
    sCodigos = "["
    Dim bPrimero As Boolean : bPrimero = True
    Do While Not rs.EOF
        If Not bPrimero Then sCodigos = sCodigos & ","
        bPrimero = False
        sCodigos = sCodigos & CStr(rs!CodPedido)
        rs.MoveNext
    Loop
    sCodigos = sCodigos & "]"
    rs.Close : Set rs = Nothing : Set db = Nothing

    If sCodigos = "[]" Then
        SyncLeerRespuestasAnulacion = True
        Exit Function
    End If

    ' Consultar host
    Dim sPayload As String
    Dim sResp    As String
    sPayload = "{""sucursal"":""" & codSuc & """,""cod_pedidos"":" & sCodigos & "}"

    If Not HttpPost(sAnulUrlLeer, sPayload, sResp) Then
        AnulacionLog "SyncLeer", "Fallo HTTP: " & sResp
        Exit Function
    End If

    ' Procesar respuesta JSON
    ProcesarRespuestasHost sResp, codSuc

    SyncLeerRespuestasAnulacion = True
    Exit Function
ErrorHandler:
    AnulacionLog "SyncLeer", "Error " & Err.Number & ": " & Err.Description
    SyncLeerRespuestasAnulacion = False
End Function

' ══════════════════════════════════════════════════════════
' Procesamiento interno de la respuesta del host
' Ejecuta la anulación local si Status=1 en el host
' ══════════════════════════════════════════════════════════
Private Sub ProcesarRespuestasHost(sJson As String, codSuc As String)
    On Error GoTo ErrorHandler

    ' Validar que sea respuesta exitosa
    If InStr(sJson, """success"":true") = 0 Then
        AnulacionLog "ProcesarRespuestas", "Respuesta sin success:true - " & Left(sJson, 200)
        Exit Sub
    End If

    ' Extraer array de registros
    Dim posStart As Long, posEnd As Long
    posStart = InStr(sJson, """registros"":[")
    If posStart = 0 Then Exit Sub
    posStart = InStr(posStart, sJson, "[") + 1

    ' Iterar cada objeto { } en el array
    Dim posObj   As Long
    Dim posObjEnd As Long
    posObj = posStart

    Do
        ' Buscar inicio de objeto
        posObj = InStr(posObj, sJson, "{")
        If posObj = 0 Then Exit Do

        ' Buscar cierre del objeto
        posObjEnd = InStr(posObj, sJson, "}")
        If posObjEnd = 0 Then Exit Do

        Dim sObj As String
        sObj = Mid(sJson, posObj, posObjEnd - posObj + 1)

        ' Extraer campos del objeto
        Dim lngCodPedido As Long
        Dim lngStatus    As Long
        Dim sMotivo      As String

        lngCodPedido = CLng(AnulExtraerJSON(sObj, "CodPedido"))
        lngStatus    = CLng(AnulExtraerJSON(sObj, "Status"))
        sMotivo      = AnulExtraerJSON(sObj, "Motivo")

        ' Si el host lo aprobó (Status=1) y aún no está ejecutado
        If lngStatus = 1 And lngCodPedido > 0 Then
            Dim lngEjecutado As Long
            On Error Resume Next
            lngEjecutado = CLng(AnulExtraerJSON(sObj, "EjecutadoEnTienda"))
            If Err.Number <> 0 Then lngEjecutado = 0 : Err.Clear
            On Error GoTo ErrorHandler

            If lngEjecutado = 0 Then
                ' Ejecutar anulación en Access
                EjecutarAnulacionLocal lngCodPedido, sMotivo, codSuc
            End If
        End If

        posObj = posObjEnd + 1
    Loop

    Exit Sub
ErrorHandler:
    AnulacionLog "ProcesarRespuestas", "Error " & Err.Number & ": " & Err.Description
End Sub

' ══════════════════════════════════════════════════════════
' Ejecuta la anulación en la BD local de Access
' y manda confirmación al host
' ══════════════════════════════════════════════════════════
Private Sub EjecutarAnulacionLocal(lngCodPedido As Long, sMotivo As String, codSuc As String)
    On Error GoTo ErrorHandler

    AnulacionLog "EjecutarLocal", "Iniciando anulación local - CodPedido=" & lngCodPedido

    ' 1) Anular el pedido en NotaDePedido
    DoCmd.SetWarnings False
    DoCmd.RunSQL "UPDATE NotaDePedido SET NotaDePedido.Anulado = -1," & _
                 " NotaDePedido.TotalGuardado = 0," & _
                 " NotaDePedido.MotivoAnulado = '" & Replace(sMotivo, "'", "''") & "'" & _
                 " WHERE NotaDePedido.CodPedido = " & lngCodPedido
    DoCmd.SetWarnings True

    ' 2) Actualizar o insertar en AnulacionPedidos local
    If existesolicitudanulacionpedido(lngCodPedido) = 1 Then
        ' Ya existe solicitud → actualizar Status=1
        DoCmd.SetWarnings False
        DoCmd.RunSQL "UPDATE AnulacionPedidos SET AnulacionPedidos.Status = 1," & _
                     " AnulacionPedidos.HoraAnulada = #" & Now & "#" & _
                     " WHERE AnulacionPedidos.CodPedido = " & lngCodPedido
        DoCmd.SetWarnings True
    Else
        ' Anulación generada desde la web sin solicitud previa en tienda
        DoCmd.SetWarnings False
        DoCmd.RunSQL "INSERT INTO AnulacionPedidos(CodPedido, HoraSolicitada, HoraAnulada, Status, Modalidad, CodPedidoCambio, Motivo)" & _
                     " VALUES (" & lngCodPedido & ", #" & Time & "#, #" & Time & "#, 1, 2, 0, '" & Replace(sMotivo, "'", "''") & "')"
        DoCmd.SetWarnings True
    End If

    AnulacionLog "EjecutarLocal", "Anulación local ejecutada - CodPedido=" & lngCodPedido

    ' 3) Sincronizar el pedido anulado al host de VentasGlobalesAccessCSV
    On Error Resume Next
    SyncVentasPedido lngCodPedido
    If Err.Number <> 0 Then
        AnulacionLog "EjecutarLocal", "Aviso: SyncVentasPedido falló para " & lngCodPedido
        Err.Clear
    End If
    On Error GoTo ErrorHandler

    ' 4) Confirmar al host que ya se ejecutó en tienda
    ConfirmarEjecucionEnHost lngCodPedido, codSuc

    Exit Sub
ErrorHandler:
    AnulacionLog "EjecutarLocal", "ERROR " & Err.Number & ": " & Err.Description
End Sub

' ══════════════════════════════════════════════════════════
' Confirmar al host que la anulación se ejecutó en tienda
' ══════════════════════════════════════════════════════════
Private Sub ConfirmarEjecucionEnHost(lngCodPedido As Long, codSuc As String)
    On Error GoTo ErrorHandler

    Dim sAnulUrlConfirm As String
    sAnulUrlConfirm = "https://proxy.batidospitaya.com/api/confirm_anulacion_pedidos.php"

    Dim sPayload As String
    Dim sResp    As String
    Dim sHora    As String

    On Error Resume Next
    sHora = Format(Now, "yyyy-mm-dd HH:nn:ss")
    If Err.Number <> 0 Then sHora = "" : Err.Clear
    On Error GoTo ErrorHandler

    sPayload = "{""sucursal"":""" & codSuc & """," & _
               """cod_pedido"":" & lngCodPedido & "," & _
               """hora_anulada"":""" & sHora & """}"

    If HttpPost(sAnulUrlConfirm, sPayload, sResp) Then
        AnulacionLog "ConfirmarHost", "OK - CodPedido=" & lngCodPedido & " | " & sResp
    Else
        AnulacionLog "ConfirmarHost", "Fallo - CodPedido=" & lngCodPedido & " | " & sResp
    End If

    Exit Sub
ErrorHandler:
    AnulacionLog "ConfirmarHost", "ERROR " & Err.Number & ": " & Err.Description
End Sub

' ══════════════════════════════════════════════════════════
' 3. MASIVO — Subir historial completo de AnulacionPedidos
'    Uso puntual para migración inicial o resincronización
' ══════════════════════════════════════════════════════════
Public Function SyncMasivoHistorialAnulaciones() As Boolean
    On Error GoTo ErrorHandler

    Dim sAnulUrlEnviar As String
    sAnulUrlEnviar = "https://proxy.batidospitaya.com/api/sync_anulacion_pedidos.php"

    Dim codSuc As String
    codSuc = CStr(codigoLocal())
    If codSuc = "0" Or codSuc = "" Then
        AnulacionLog "SyncMasivo", "codigoLocal() inválido."
        Exit Function
    End If

    Dim db As DAO.Database
    Dim rs As DAO.Recordset
    Set db = CurrentDb()

    ' Traer TODOS los registros (sin filtro de Status)
    Set rs = db.OpenRecordset( _
        "SELECT * FROM AnulacionPedidos ORDER BY CodAnulacionPedidos ASC", _
        dbOpenForwardOnly, dbReadOnly)

    If rs.EOF And rs.BOF Then
        MsgBox "No hay registros en AnulacionPedidos.", vbInformation, "Sync Masivo"
        rs.Close : Set rs = Nothing : Set db = Nothing
        SyncMasivoHistorialAnulaciones = True
        Exit Function
    End If

    ' Enviar en bloques de 50 registros
    Dim bloqueSize   As Integer : bloqueSize = 50
    Dim totalEnviado As Long    : totalEnviado = 0
    Dim totalOK      As Long    : totalOK = 0
    Dim sJsonBloque  As String
    Dim sResp        As String
    Dim contador     As Integer : contador = 0
    Dim sRows        As String  : sRows = "["
    Dim bPrimero     As Boolean : bPrimero = True

    Do While Not rs.EOF
        Dim sRow As String
        sRow = BuildAnulacionRowJSON(rs, codSuc)
        If Not bPrimero Then sRows = sRows & ","
        bPrimero = False
        sRows = sRows & sRow
        contador = contador + 1
        totalEnviado = totalEnviado + 1

        If contador >= bloqueSize Then
            sRows = sRows & "]"
            Dim sPayload As String
            sPayload = "{""sucursal"":""" & codSuc & """,""modo"":""masivo"",""rows"":" & sRows & "}"
            If HttpPost(sAnulUrlEnviar, sPayload, sResp) Then
                totalOK = totalOK + contador
            End If
            sRows = "[" : bPrimero = True : contador = 0
        End If

        rs.MoveNext
    Loop

    ' Enviar último bloque si quedan registros
    If contador > 0 Then
        sRows = sRows & "]"
        Dim sPayloadFinal As String
        sPayloadFinal = "{""sucursal"":""" & codSuc & """,""modo"":""masivo"",""rows"":" & sRows & "}"
        If HttpPost(sAnulUrlEnviar, sPayloadFinal, sResp) Then
            totalOK = totalOK + contador
        End If
    End If

    rs.Close : Set rs = Nothing : Set db = Nothing

    MsgBox "Sync Masivo completado." & vbCrLf & _
           "Total enviados: " & totalEnviado & vbCrLf & _
           "OK: " & totalOK, vbInformation, "Sync Masivo Anulaciones"

    SyncMasivoHistorialAnulaciones = (totalOK = totalEnviado)
    Exit Function
ErrorHandler:
    AnulacionLog "SyncMasivo", "Error " & Err.Number & ": " & Err.Description
    SyncMasivoHistorialAnulaciones = False
End Function

' ══════════════════════════════════════════════════════════
' Construir JSON de un Recordset de AnulacionPedidos
' (solo Status=0 por defecto, para el envío de pendientes)
' ══════════════════════════════════════════════════════════
Private Function BuildAnulacionesJSON(rs As DAO.Recordset, codSuc As String) As String
    Dim sJSON    As String
    Dim bPrimero As Boolean

    sJSON = "["
    bPrimero = True

    Do While Not rs.EOF
        If Not bPrimero Then sJSON = sJSON & ","
        bPrimero = False
        sJSON = sJSON & BuildAnulacionRowJSON(rs, codSuc)
        rs.MoveNext
    Loop

    sJSON = sJSON & "]"
    BuildAnulacionesJSON = sJSON
End Function

' ══════════════════════════════════════════════════════════
' Construir JSON de una sola fila de AnulacionPedidos
' ══════════════════════════════════════════════════════════
Private Function BuildAnulacionRowJSON(rs As DAO.Recordset, codSuc As String) As String
    On Error Resume Next

    Dim sHoraSol As String : sHoraSol = ""
    Dim sHoraAnu As String : sHoraAnu = ""

    sHoraSol = Format(CDate(Nz(rs!HoraSolicitada, "")), "yyyy-mm-dd HH:nn:ss")
    If Err.Number <> 0 Then sHoraSol = "" : Err.Clear

    sHoraAnu = Format(CDate(Nz(rs!HoraAnulada, "")), "yyyy-mm-dd HH:nn:ss")
    If Err.Number <> 0 Then sHoraAnu = "" : Err.Clear

    Dim sRow As String
    sRow = "{"
    sRow = sRow & """CodPedido"":"           & AnulJSONVal(rs!CodPedido)           & ","
    sRow = sRow & """HoraSolicitada"":"       & AnulJSONStr(sHoraSol)               & ","
    sRow = sRow & """HoraAnulada"":"          & AnulJSONStr(sHoraAnu)               & ","
    sRow = sRow & """Status"":"               & AnulJSONVal(rs!Status)              & ","
    sRow = sRow & """Modalidad"":"            & AnulJSONVal(rs!Modalidad)           & ","
    sRow = sRow & """CodPedidoCambio"":"      & AnulJSONVal(rs!CodPedidoCambio)     & ","
    sRow = sRow & """Motivo"":"               & AnulJSONStr(rs!Motivo)              & ","
    sRow = sRow & """CodMotivoAnulacion"":"   & AnulJSONVal(rs!CodMotivoAnulacion)  & ","
    sRow = sRow & """Sucursal"":"             & AnulJSONStr(codSuc)
    sRow = sRow & "}"

    On Error GoTo 0
    BuildAnulacionRowJSON = sRow
End Function

' ══════════════════════════════════════════════════════════
' HTTP POST helper (reutilizable)
' ══════════════════════════════════════════════════════════
Private Function HttpPost(sURL As String, sPayload As String, ByRef sRespOut As String) As Boolean
    On Error GoTo ErrorHandler

    Dim sAnulToken  As String : sAnulToken  = "a8f5e2d9c4b7a1e6f3d8c5b2a9e6d3f0c7a4b1e8d5c2a9f6e3d0c7b4a1e8f5d2"
    Dim lAnulTimeout As Long  : lAnulTimeout = 15000

    Dim http As Object
    Set http = CreateObject("MSXML2.ServerXMLHTTP.6.0")
    http.Open "POST", sURL, False
    http.setRequestHeader "Content-Type", "application/json; charset=utf-8"
    http.setRequestHeader "Authorization", "Bearer " & sAnulToken
    http.setRequestHeader "User-Agent", "PitayaAccess/1.0"
    http.setTimeouts 5000, 5000, lAnulTimeout, lAnulTimeout
    http.Send sPayload

    sRespOut = http.responseText
    HttpPost = (http.Status = 200 And InStr(sRespOut, """success"":true") > 0)

    Set http = Nothing
    Exit Function
ErrorHandler:
    AnulacionLog "HttpPost", "Error " & Err.Number & ": " & Err.Description
    Set http = Nothing
    HttpPost = False
End Function

' ══════════════════════════════════════════════════════════
' Extraer valor de un campo en un objeto JSON simple
' (parser mínimo para respuestas planas)
' ══════════════════════════════════════════════════════════
Private Function AnulExtraerJSON(sJson As String, sKey As String) As String
    Dim sSearch  As String
    Dim posStart As Long
    Dim posEnd   As Long
    Dim sVal     As String

    sSearch = """" & sKey & """:"
    posStart = InStr(sJson, sSearch)
    If posStart = 0 Then
        AnulExtraerJSON = ""
        Exit Function
    End If

    posStart = posStart + Len(sSearch)

    ' Saltar espacios
    Do While Mid(sJson, posStart, 1) = " "
        posStart = posStart + 1
    Loop

    Dim cFirst As String
    cFirst = Mid(sJson, posStart, 1)

    If cFirst = """" Then
        ' Valor string: buscar cierre de comilla
        posStart = posStart + 1
        posEnd   = InStr(posStart, sJson, """")
        If posEnd = 0 Then posEnd = Len(sJson) + 1
        sVal = Mid(sJson, posStart, posEnd - posStart)
    Else
        ' Valor numérico o null: buscar coma o }
        posEnd = posStart
        Do While posEnd <= Len(sJson)
            Dim c As String
            c = Mid(sJson, posEnd, 1)
            If c = "," Or c = "}" Or c = "]" Then Exit Do
            posEnd = posEnd + 1
        Loop
        sVal = Trim(Mid(sJson, posStart, posEnd - posStart))
        If sVal = "null" Then sVal = ""
    End If

    AnulExtraerJSON = sVal
End Function

' ══════════════════════════════════════════════════════════
' JSON helpers para anulaciones
' ══════════════════════════════════════════════════════════
Private Function AnulJSONVal(v As Variant) As String
    If IsNull(v) Or IsEmpty(v) Then
        AnulJSONVal = "null"
    ElseIf IsNumeric(v) Then
        AnulJSONVal = Replace(CStr(CDbl(v)), ",", ".")
    Else
        AnulJSONVal = """" & AnulJSONEsc(CStr(v)) & """"
    End If
End Function

Private Function AnulJSONStr(v As Variant) As String
    If IsNull(v) Or IsEmpty(v) Or CStr(v) = "" Then
        AnulJSONStr = "null"
    Else
        AnulJSONStr = """" & AnulJSONEsc(CStr(v)) & """"
    End If
End Function

Private Function AnulJSONEsc(s As String) As String
    s = Replace(s, "\", "\\")
    s = Replace(s, """", "\""")
    s = Replace(s, Chr(10), "\n")
    s = Replace(s, Chr(13), "\r")
    s = Replace(s, Chr(9), "\t")
    AnulJSONEsc = s
End Function

' ══════════════════════════════════════════════════════════
' Log de eventos (Debug + tabla AnulacionSyncLog si existe)
' ══════════════════════════════════════════════════════════
Private Sub AnulacionLog(sOrigen As String, sMensaje As String)
    Debug.Print "[SyncAnulaciones][" & sOrigen & "] " & sMensaje

    On Error Resume Next
    Dim db As DAO.Database
    Dim rs As DAO.Recordset
    Set db = CurrentDb()
    Set rs = db.OpenRecordset("SELECT * FROM AnulacionSyncLog WHERE 1=0", dbOpenDynaset)
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
' Diagnóstico manual
' ══════════════════════════════════════════════════════════
Public Sub ProbarSyncAnulaciones()
    MsgBox "Enviando pendientes al host...", vbInformation, "Test"
    If SyncEnviarAnulacionesPendientes() Then
        MsgBox "Envío OK.", vbInformation, "Sync Anulaciones"
    Else
        MsgBox "Error en envío. Ver Ctrl+G.", vbExclamation, "Sync Anulaciones"
    End If

    MsgBox "Leyendo respuestas del host...", vbInformation, "Test"
    If SyncLeerRespuestasAnulacion() Then
        MsgBox "Lectura OK.", vbInformation, "Sync Anulaciones"
    Else
        MsgBox "Error en lectura. Ver Ctrl+G.", vbExclamation, "Sync Anulaciones"
    End If
End Sub
