' =============================================================
' Módulo: modSyncCierreDepositos
' Propósito: Sincronización unidireccional Access → Host MySQL
'            de las tablas CierreDiario, Depositos y EstadoInicial.
'
' FLUJO DIARIO (cierre de caja):
'   SyncCierreDiario30Dias()
'     → Elimina últimos 30 días del host y re-sube ese período.
'   SyncDepositos30Dias()
'     → Elimina últimos 30 días del host y re-sube ese período.
'   SyncEstadoInicial30Dias()
'     → Elimina últimos 30 días del host y re-sube ese período.
'   SyncCierreDepositosTienda30Dias()
'     → Corre las tres funciones anteriores juntas (botón de cierre).
'
' FLUJO MASIVO (historial completo, botón panel admin):
'   SyncCierreDiarioMasivo()
'   SyncDepositosMasivo()
'   SyncEstadoInicialMasivo()
'   SyncCierreDepositosTiendaMasivo()
'     → Elimina TODO de esta sucursal y re-sube toda la tabla.
'
' INSTALACIÓN:
'   Alt+F11 → Archivo → Importar → modulo_sync_cierre_depositos.bas
'
' NOTA sobre nombres de tablas Access:
'   CierreDiario  → tabla Access "CierreDiario"  (ajustar si es distinto)
'   Depositos     → tabla Access "Depositos"     (ajustar si es distinto)
'   EstadoInicial → tabla Access "EstadoInicial" (ajustar si es distinto)
' =============================================================

Option Explicit

Private Const SCD_TOKEN      As String  = "a8f5e2d9c4b7a1e6f3d8c5b2a9e6d3f0c7a4b1e8d5c2a9f6e3d0c7b4a1e8f5d2"
Private Const SCD_BASE_URL   As String  = "https://proxy.batidospitaya.com/api/"
Private Const SCD_BATCH_SIZE As Integer = 200

' ══════════════════════════════════════════════════════════
' FUNCIONES PÚBLICAS — CIERRE DIARIO (últimos 30 días)
' ══════════════════════════════════════════════════════════

Public Function SyncCierreDiario30Dias() As Boolean
    Dim c As String : c = CStr(codigoLocal())
    Dim f As String : f = Format(DateAdd("d", -30, Date()), "yyyy-mm-dd")
    SyncCierreDiario30Dias = ScdSyncTabla( _
        SCD_BASE_URL & "sync_cierre_diario.php", _
        "SELECT * FROM CierreDiario WHERE Fecha >= #" & f & "#", _
        "limpiar_30dias", "CD", c)
End Function

Public Function SyncDepositos30Dias() As Boolean
    Dim c As String : c = CStr(codigoLocal())
    Dim f As String : f = Format(DateAdd("d", -30, Date()), "yyyy-mm-dd")
    SyncDepositos30Dias = ScdSyncTabla( _
        SCD_BASE_URL & "sync_depositos.php", _
        "SELECT * FROM Depositos WHERE Fecha >= #" & f & "#", _
        "limpiar_30dias", "DEP", c)
End Function

Public Function SyncEstadoInicial30Dias() As Boolean
    Dim c As String : c = CStr(codigoLocal())
    Dim f As String : f = Format(DateAdd("d", -30, Date()), "yyyy-mm-dd")
    SyncEstadoInicial30Dias = ScdSyncTabla( _
        SCD_BASE_URL & "sync_estado_inicial.php", _
        "SELECT * FROM EstadoInicial WHERE Fecha >= #" & f & "#", _
        "limpiar_30dias", "EI", c)
End Function

' ── Master cierre TIENDA (llama las tres tablas juntas) ───
' Llamar en el procedimiento de cierre de caja de cada tienda.
Public Function SyncCierreDepositosTienda30Dias() As Boolean
    Dim bOk As Boolean : bOk = True
    ScdLog "CierreTienda", "Iniciando sync 30 dias - " & Now()
    If Not SyncCierreDiario30Dias()   Then bOk = False
    If Not SyncDepositos30Dias()      Then bOk = False
    If Not SyncEstadoInicial30Dias()  Then bOk = False
    ScdLog "CierreTienda", IIf(bOk, "OK", "Con errores") & " - " & Now()
    SyncCierreDepositosTienda30Dias = bOk
End Function

' ══════════════════════════════════════════════════════════
' FUNCIONES PÚBLICAS — MASIVO (historial completo)
' ══════════════════════════════════════════════════════════

Public Function SyncCierreDiarioMasivo() As Boolean
    Dim c As String : c = CStr(codigoLocal())
    SyncCierreDiarioMasivo = ScdSyncTabla( _
        SCD_BASE_URL & "sync_cierre_diario.php", _
        "SELECT * FROM CierreDiario ORDER BY CodigoCierre", _
        "limpiar_total", "CD", c)
End Function

Public Function SyncDepositosMasivo() As Boolean
    Dim c As String : c = CStr(codigoLocal())
    SyncDepositosMasivo = ScdSyncTabla( _
        SCD_BASE_URL & "sync_depositos.php", _
        "SELECT * FROM Depositos ORDER BY CodDeposito", _
        "limpiar_total", "DEP", c)
End Function

Public Function SyncEstadoInicialMasivo() As Boolean
    Dim c As String : c = CStr(codigoLocal())
    SyncEstadoInicialMasivo = ScdSyncTabla( _
        SCD_BASE_URL & "sync_estado_inicial.php", _
        "SELECT * FROM EstadoInicial ORDER BY CodCajaInicial", _
        "limpiar_total", "EI", c)
End Function

' ── Master masivo TIENDA ─────────────────────────────────
' Activar desde botón panel admin.
Public Function SyncCierreDepositosTiendaMasivo() As Boolean
    Dim bOk As Boolean : bOk = True
    ScdLog "MasivoTienda", "Iniciando masivo - " & Now()
    If Not SyncCierreDiarioMasivo()    Then bOk = False
    If Not SyncDepositosMasivo()       Then bOk = False
    If Not SyncEstadoInicialMasivo()   Then bOk = False
    ScdLog "MasivoTienda", IIf(bOk, "OK", "Con errores") & " - " & Now()
    MsgBox "Masivo Cierre/Depósitos/EstadoInicial " & IIf(bOk, "OK.", "con errores (ver log)."), _
           IIf(bOk, vbInformation, vbExclamation), "Sync Cierre & Depósitos Masivo"
    SyncCierreDepositosTiendaMasivo = bOk
End Function

' ══════════════════════════════════════════════════════════
' MOTOR GENÉRICO: Limpiar + Enviar en batches de 200
' ══════════════════════════════════════════════════════════
Private Function ScdSyncTabla(sUrl As String, sSQL As String, _
    sModoLimpiar As String, sTablaId As String, codSuc As String) As Boolean
    On Error GoTo EH

    Dim sResp As String

    ' Paso 1: limpiar el rango en el host
    If Not ScdLimpiar(sUrl, codSuc, sModoLimpiar, sResp) Then
        ScdLog sTablaId, "Limpiar fallo: " & Left(sResp, 200)
        ScdSyncTabla = False : Exit Function
    End If
    ScdLog sTablaId, "Limpiar OK (" & sModoLimpiar & ")"

    ' Paso 2: leer local y enviar en bloques
    Dim db As DAO.Database : Set db = CurrentDb()
    Dim rs As DAO.Recordset
    Set rs = db.OpenRecordset(sSQL, dbOpenForwardOnly, dbReadOnly)

    If rs.EOF And rs.BOF Then
        rs.Close : Set rs = Nothing : Set db = Nothing
        ScdLog sTablaId, "Sin registros locales para enviar."
        ScdSyncTabla = True : Exit Function
    End If

    Dim bOk    As Boolean : bOk    = True
    Dim sRows  As String  : sRows  = "["
    Dim bFirst As Boolean : bFirst = True
    Dim nCount As Long    : nCount = 0
    Dim nTotal As Long    : nTotal = 0

    Do While Not rs.EOF
        If Not bFirst Then sRows = sRows & ","
        bFirst = False
        sRows  = sRows & ScdBuildRow(rs, codSuc, sTablaId)
        nCount = nCount + 1
        nTotal = nTotal + 1

        If nCount >= SCD_BATCH_SIZE Then
            sRows = sRows & "]"
            If Not ScdEnviarBatch(sUrl, codSuc, sRows, sResp) Then bOk = False
            sRows = "[" : bFirst = True : nCount = 0
        End If
        rs.MoveNext
    Loop

    ' Enviar último bloque parcial
    If nCount > 0 Then
        sRows = sRows & "]"
        If Not ScdEnviarBatch(sUrl, codSuc, sRows, sResp) Then bOk = False
    End If

    rs.Close : Set rs = Nothing : Set db = Nothing
    ScdLog sTablaId, "Fin. Total=" & nTotal & " | " & IIf(bOk, "OK", "Con errores")
    ScdSyncTabla = bOk
    Exit Function
EH:
    ScdLog sTablaId, "Error " & Err.Number & ": " & Err.Description
    On Error Resume Next
    If Not rs Is Nothing Then rs.Close
    Set rs = Nothing : Set db = Nothing
    On Error GoTo 0
    ScdSyncTabla = False
End Function

' ── Despachar al builder correcto ────────────────────────
Private Function ScdBuildRow(rs As DAO.Recordset, codSuc As String, tid As String) As String
    Select Case tid
        Case "CD"  : ScdBuildRow = ScdRowCD(rs, codSuc)
        Case "DEP" : ScdBuildRow = ScdRowDEP(rs, codSuc)
        Case "EI"  : ScdBuildRow = ScdRowEI(rs, codSuc)
        Case Else  : ScdBuildRow = "{}"
    End Select
End Function

' ══════════════════════════════════════════════════════════
' BUILDERS DE FILA JSON — uno por tabla
' ══════════════════════════════════════════════════════════

' CierreDiario
Private Function ScdRowCD(rs As DAO.Recordset, c As String) As String
    On Error Resume Next
    Dim s As String : s = "{"
    s = s & """CodigoCierre"":"       & ScdVal(rs!CodigoCierre)       & ","
    s = s & """HoraInicial"":"        & ScdFechaHora(rs!HoraInicial)  & ","
    s = s & """HoraFinal"":"          & ScdFechaHora(rs!HoraFinal)    & ","
    s = s & """Fecha"":"              & ScdFechaHora(rs!Fecha)         & ","
    s = s & """CodOperario"":"        & ScdVal(rs!CodOperario)         & ","
    s = s & """MFCor"":"              & ScdVal(rs!MFCor)               & ","
    s = s & """MFDol"":"              & ScdVal(rs!MFDol)               & ","
    s = s & """Faltante"":"           & ScdVal(rs!Faltante)            & ","
    s = s & """TotalHugo"":"          & ScdVal(rs!TotalHugo)           & ","
    s = s & """TotalPedidosYa"":"     & ScdVal(rs!TotalPedidosYa)      & ","
    s = s & """TotalTransferencia"":" & ScdVal(rs!TotalTransferencia)  & ","
    s = s & """TotalPOS"":"           & ScdVal(rs!TotalPOS)            & ","
    s = s & """Observaciones"":"      & ScdStr(rs!Observaciones)       & ","
    s = s & """Sucursal"":"           & ScdStr(c)
    s = s & "}"
    On Error GoTo 0 : ScdRowCD = s
End Function

' Depositos
Private Function ScdRowDEP(rs As DAO.Recordset, c As String) As String
    On Error Resume Next
    Dim s As String : s = "{"
    s = s & """CodDeposito"":"  & ScdVal(rs!CodDeposito)   & ","
    s = s & """Monto"":"        & ScdVal(rs!Monto)          & ","
    s = s & """Denominacion"":" & ScdStr(rs!Denominacion)   & ","
    s = s & """Tipo"":"         & ScdStr(rs!Tipo)            & ","
    s = s & """Fecha"":"        & ScdFecha(rs!Fecha)         & ","
    s = s & """Observacion"":"  & ScdStr(rs!Observacion)    & ","
    s = s & """DuranteTurno"":" & ScdVal(rs!DuranteTurno)   & ","
    s = s & """Sucursal"":"     & ScdStr(c)
    s = s & "}"
    On Error GoTo 0 : ScdRowDEP = s
End Function

' EstadoInicial
Private Function ScdRowEI(rs As DAO.Recordset, c As String) As String
    On Error Resume Next
    Dim s As String : s = "{"
    s = s & """CodCajaInicial"":"    & ScdVal(rs!CodCajaInicial)              & ","
    s = s & """Dinero"":"            & ScdVal(rs!Dinero)                       & ","
    s = s & """Fecha"":"             & ScdFechaHora(rs!Fecha)                  & ","
    s = s & """Selladora"":"         & ScdVal(rs!Selladora)                    & ","
    s = s & """TipoCambio$_C$"":"    & ScdVal(rs![TipoCambio$_C$])             & ","
    s = s & """Feriado"":"           & ScdVal(rs!Feriado)                      & ","
    s = s & """Observaciones"":"     & ScdStr(rs!Observaciones)                & ","
    s = s & """Eventos"":"           & ScdStr(rs!Eventos)                      & ","
    s = s & """Sucursal"":"          & ScdStr(c)                               & ","
    s = s & """FechaUltimoSync"":"   & ScdFechaHora(Now())
    s = s & "}"
    On Error GoTo 0 : ScdRowEI = s
End Function

' ══════════════════════════════════════════════════════════
' HTTP HELPERS
' ══════════════════════════════════════════════════════════

Private Function ScdLimpiar(sUrl As String, codSuc As String, _
    sModo As String, ByRef sRespOut As String) As Boolean
    Dim sPayload As String
    sPayload = "{""sucursal"":" & codSuc & ",""modo"":""" & sModo & """}"
    ScdLimpiar = ScdHttpPost(sUrl, sPayload, sRespOut)
End Function

Private Function ScdEnviarBatch(sUrl As String, codSuc As String, _
    sRows As String, ByRef sRespOut As String) As Boolean
    Dim sPayload As String
    sPayload = "{""sucursal"":" & codSuc & ",""modo"":""insertar"",""rows"":" & sRows & "}"
    ScdEnviarBatch = ScdHttpPost(sUrl, sPayload, sRespOut)
End Function

Private Function ScdHttpPost(sUrl As String, sPayload As String, _
    ByRef sRespOut As String) As Boolean
    On Error GoTo EH
    Dim http As Object
    Set http = CreateObject("MSXML2.ServerXMLHTTP.6.0")
    http.Open "POST", sUrl, False
    http.setRequestHeader "Content-Type", "application/json; charset=utf-8"
    http.setRequestHeader "Authorization", "Bearer " & SCD_TOKEN
    http.setRequestHeader "User-Agent", "PitayaAccess/1.0"
    http.setTimeouts 5000, 5000, 30000, 30000
    http.Send sPayload
    sRespOut = http.responseText
    ScdHttpPost = (http.Status = 200 And InStr(sRespOut, """success"":true") > 0)
    Set http = Nothing
    Exit Function
EH:
    ScdLog "HttpPost", "Error " & Err.Number & ": " & Err.Description
    Set http = Nothing
    ScdHttpPost = False
End Function

' ══════════════════════════════════════════════════════════
' JSON HELPERS
' ══════════════════════════════════════════════════════════

Private Function ScdVal(v As Variant) As String
    If IsNull(v) Or IsEmpty(v) Then
        ScdVal = "null"
    ElseIf IsNumeric(v) Then
        ScdVal = Replace(CStr(CDbl(v)), ",", ".")
    Else
        ScdVal = """" & ScdEsc(CStr(v)) & """"
    End If
End Function

Private Function ScdStr(v As Variant) As String
    If IsNull(v) Or IsEmpty(v) Or CStr(v) = "" Then
        ScdStr = "null"
    Else
        ScdStr = """" & ScdEsc(CStr(v)) & """"
    End If
End Function

' Fecha sólo (DATE) → "yyyy-mm-dd"
Private Function ScdFecha(v As Variant) As String
    On Error Resume Next
    If IsNull(v) Or IsEmpty(v) Then ScdFecha = "null" : Exit Function
    Dim s As String
    s = Format(CDate(v), "yyyy-mm-dd")
    If Err.Number <> 0 Or s = "" Then ScdFecha = "null" : Err.Clear : Exit Function
    ScdFecha = """" & s & """"
    On Error GoTo 0
End Function

' Fecha + Hora (DATETIME) → "yyyy-mm-dd HH:nn:ss"
Private Function ScdFechaHora(v As Variant) As String
    On Error Resume Next
    If IsNull(v) Or IsEmpty(v) Then ScdFechaHora = "null" : Exit Function
    Dim s As String
    s = Format(CDate(v), "yyyy-mm-dd HH:nn:ss")
    If Err.Number <> 0 Or s = "" Then ScdFechaHora = "null" : Err.Clear : Exit Function
    ScdFechaHora = """" & s & """"
    On Error GoTo 0
End Function

Private Function ScdEsc(s As String) As String
    s = Replace(s, "\", "\\")
    s = Replace(s, """", "\""")
    s = Replace(s, Chr(10), "\n")
    s = Replace(s, Chr(13), "\r")
    s = Replace(s, Chr(9), "\t")
    ScdEsc = s
End Function

' ══════════════════════════════════════════════════════════
' LOG (Debug.Print + tabla KardexSyncLog si existe)
' ══════════════════════════════════════════════════════════
Private Sub ScdLog(sOrigen As String, sMensaje As String)
    Debug.Print "[SyncCierreDepositos][" & sOrigen & "] " & sMensaje

    On Error Resume Next
    Dim db As DAO.Database
    Dim rs As DAO.Recordset
    Set db = CurrentDb()
    Set rs = db.OpenRecordset("SELECT * FROM KardexSyncLog WHERE 1=0", dbOpenDynaset)
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
