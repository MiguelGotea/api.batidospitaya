' =============================================================
' Módulo: modSyncKardex
' Propósito: Sincronización unidireccional Access → Host MySQL
'            de las tablas del Kardex de Productos.
'
' FLUJO DIARIO (cierre de caja):
'   SyncKardexCierre30Dias()
'     → Por cada tabla: elimina últimos 30 días del host
'       y re-sube todos los registros locales de ese período.
'
' FLUJO MASIVO (historial completo, botón panel admin):
'   SyncKardexMasivoCompleto()
'     → Por cada tabla: elimina TODO lo de esta sucursal en el
'       host y re-sube toda la tabla local en bloques de 200.
'
' FUNCIONES INDIVIDUALES (insertar en puntos específicos):
'   SyncKardexInventarioCotizacion30Dias() / Masivo()
'   SyncKardexAjustesInventario30Dias()    / Masivo()
'   SyncKardexCompras30Dias()              / Masivo()
'   SyncKardexMermaCotizacion30Dias()      / Masivo()
'   SyncKardexPreIngresos30Dias()          / Masivo()
'   SyncKardexSubPreIngresos30Dias()       / Masivo()
'
' INSTALACIÓN:
'   Alt+F11 → Archivo → Importar → modulo_sync_kardex.bas
' =============================================================

Option Explicit

Private Const KDX_TOKEN      As String  = "a8f5e2d9c4b7a1e6f3d8c5b2a9e6d3f0c7a4b1e8d5c2a9f6e3d0c7b4a1e8f5d2"
Private Const KDX_BASE_URL   As String  = "https://proxy.batidospitaya.com/api/"
Private Const KDX_BATCH_SIZE As Integer = 200

' ══════════════════════════════════════════════════════════
' FUNCIONES PÚBLICAS — CIERRE DIARIO (últimos 30 días)
' ══════════════════════════════════════════════════════════

Public Function SyncKardexInventarioCotizacion30Dias() As Boolean
    Dim c As String : c = CStr(codigoLocal())
    Dim f As String : f = Format(DateAdd("d", -30, Date()), "yyyy-mm-dd")
    SyncKardexInventarioCotizacion30Dias = KdxSyncTabla( _
        KDX_BASE_URL & "sync_kardex_inventario_cotizacion.php", _
        "SELECT * FROM [Inventario Cotizacion] WHERE Fecha >= #" & f & "#", _
        "limpiar_30dias", "IC", c)
End Function

Public Function SyncKardexAjustesInventario30Dias() As Boolean
    Dim c As String : c = CStr(codigoLocal())
    Dim f As String : f = Format(DateAdd("d", -30, Date()), "yyyy-mm-dd")
    SyncKardexAjustesInventario30Dias = KdxSyncTabla( _
        KDX_BASE_URL & "sync_kardex_ajustes_inventario.php", _
        "SELECT * FROM AjustesInventario WHERE Fecha >= #" & f & "#", _
        "limpiar_30dias", "AI", c)
End Function

Public Function SyncKardexCompras30Dias() As Boolean
    Dim c As String : c = CStr(codigoLocal())
    Dim f As String : f = Format(DateAdd("d", -30, Date()), "yyyy-mm-dd")
    SyncKardexCompras30Dias = KdxSyncTabla( _
        KDX_BASE_URL & "sync_kardex_compras.php", _
        "SELECT * FROM Compras WHERE Fecha >= #" & f & "#", _
        "limpiar_30dias", "C", c)
End Function

Public Function SyncKardexMermaCotizacion30Dias() As Boolean
    Dim c As String : c = CStr(codigoLocal())
    Dim f As String : f = Format(DateAdd("d", -30, Date()), "yyyy-mm-dd")
    SyncKardexMermaCotizacion30Dias = KdxSyncTabla( _
        KDX_BASE_URL & "sync_kardex_merma_cotizacion.php", _
        "SELECT * FROM [Merma Cotizacion] WHERE Fecha >= #" & f & "#", _
        "limpiar_30dias", "MC", c)
End Function

Public Function SyncKardexPreIngresos30Dias() As Boolean
    Dim c As String : c = CStr(codigoLocal())
    Dim f As String : f = Format(DateAdd("d", -30, Date()), "yyyy-mm-dd")
    SyncKardexPreIngresos30Dias = KdxSyncTabla( _
        KDX_BASE_URL & "sync_kardex_preingresos.php", _
        "SELECT * FROM PreIngresoPitaya WHERE Fecha >= #" & f & "#", _
        "limpiar_30dias", "PI", c)
End Function

Public Function SyncKardexSubPreIngresos30Dias() As Boolean
    ' Sin campo Fecha propio: filtra por Fecha del padre PreIngresoPitaya
    Dim c As String : c = CStr(codigoLocal())
    Dim f As String : f = Format(DateAdd("d", -30, Date()), "yyyy-mm-dd")
    Dim sSQL As String
    sSQL = "SELECT [SubPreIngresosPitaya].* " & _
           "FROM [SubPreIngresosPitaya] INNER JOIN PreIngresoPitaya " & _
           "ON [SubPreIngresosPitaya].CodPreIngresoPitaya = PreIngresoPitaya.CodPreIngresoPitaya " & _
           "WHERE PreIngresoPitaya.Fecha >= #" & f & "#"
    SyncKardexSubPreIngresos30Dias = KdxSyncTabla( _
        KDX_BASE_URL & "sync_kardex_sub_preingresos.php", _
        sSQL, "limpiar_30dias", "SPI", c)
End Function

' ── Master cierre TIENDA (sucursales) ───────────────────
' Tablas: InventarioCotizacion, AjustesInventario, Compras, MermaCotizacion
' Llamar en el procedimiento de cierre de caja de cada tienda.
Public Function SyncKardexTiendaCierre30Dias() As Boolean
    Dim bOk As Boolean : bOk = True
    KdxLog "CierreTienda", "Iniciando sync 30 dias tienda - " & Now()
    If Not SyncKardexInventarioCotizacion30Dias() Then bOk = False
    If Not SyncKardexAjustesInventario30Dias()    Then bOk = False
    If Not SyncKardexCompras30Dias()              Then bOk = False
    If Not SyncKardexMermaCotizacion30Dias()      Then bOk = False
    KdxLog "CierreTienda", IIf(bOk, "OK", "Con errores") & " - " & Now()
    SyncKardexTiendaCierre30Dias = bOk
End Function

' ── Master cierre CENTRAL (codigoLocal()=0) ─────────────
' Tablas: Compras, PreIngresoPitaya, SubPreIngresosPitaya
' Llamar en el procedimiento de cierre del sistema central.
Public Function SyncKardexCentralCierre30Dias() As Boolean
    Dim bOk As Boolean : bOk = True
    KdxLog "Cierre Central", "Iniciando sync 30 dias central - " & Now()
    If Not SyncKardexCompras30Dias()         Then bOk = False
    If Not SyncKardexPreIngresos30Dias()     Then bOk = False
    If Not SyncKardexSubPreIngresos30Dias()  Then bOk = False
    KdxLog "CierreCentral", IIf(bOk, "OK", "Con errores") & " - " & Now()
    SyncKardexCentralCierre30Dias = bOk
End Function

' ══════════════════════════════════════════════════════════
' FUNCIONES PÚBLICAS — MASIVO (historial completo)
' ══════════════════════════════════════════════════════════

Public Function SyncKardexInventarioCotizacionMasivo() As Boolean
    Dim c As String : c = CStr(codigoLocal())
    SyncKardexInventarioCotizacionMasivo = KdxSyncTabla( _
        KDX_BASE_URL & "sync_kardex_inventario_cotizacion.php", _
        "SELECT * FROM [Inventario Cotizacion] ORDER BY CodICotizacion", _
        "limpiar_total", "IC", c)
End Function

Public Function SyncKardexAjustesInventarioMasivo() As Boolean
    Dim c As String : c = CStr(codigoLocal())
    SyncKardexAjustesInventarioMasivo = KdxSyncTabla( _
        KDX_BASE_URL & "sync_kardex_ajustes_inventario.php", _
        "SELECT * FROM AjustesInventario ORDER BY CodAjustesInventario", _
        "limpiar_total", "AI", c)
End Function

Public Function SyncKardexComprasMasivo() As Boolean
    Dim c As String : c = CStr(codigoLocal())
    SyncKardexComprasMasivo = KdxSyncTabla( _
        KDX_BASE_URL & "sync_kardex_compras.php", _
        "SELECT * FROM Compras ORDER BY CodIngresoAlmacen", _
        "limpiar_total", "C", c)
End Function

Public Function SyncKardexMermaCotizacionMasivo() As Boolean
    Dim c As String : c = CStr(codigoLocal())
    SyncKardexMermaCotizacionMasivo = KdxSyncTabla( _
        KDX_BASE_URL & "sync_kardex_merma_cotizacion.php", _
        "SELECT * FROM [Merma Cotizacion] ORDER BY CodMermaUnidad", _
        "limpiar_total", "MC", c)
End Function

Public Function SyncKardexPreIngresosMasivo() As Boolean
    Dim c As String : c = CStr(codigoLocal())
    SyncKardexPreIngresosMasivo = KdxSyncTabla( _
        KDX_BASE_URL & "sync_kardex_preingresos.php", _
        "SELECT * FROM PreIngresoPitaya ORDER BY CodPreIngresoPitaya", _
        "limpiar_total", "PI", c)
End Function

Public Function SyncKardexSubPreIngresosMasivo() As Boolean
    Dim c As String : c = CStr(codigoLocal())
    SyncKardexSubPreIngresosMasivo = KdxSyncTabla( _
        KDX_BASE_URL & "sync_kardex_sub_preingresos.php", _
        "SELECT * FROM SubPreIngresosPitaya ORDER BY CodSubPreIngresoPitaya", _
        "limpiar_total", "SPI", c)
End Function

' ── Master masivo TIENDA (sucursales) ───────────────────
' Tablas: InventarioCotizacion, AjustesInventario, Compras, MermaCotizacion
' Activar desde botón panel admin en cada sistema de tienda.
Public Function SyncKardexTiendaMasivoCompleto() As Boolean
    Dim bOk As Boolean : bOk = True
    KdxLog "MasivoTienda", "Iniciando masivo tienda - " & Now()
    If Not SyncKardexInventarioCotizacionMasivo() Then bOk = False
    If Not SyncKardexAjustesInventarioMasivo()    Then bOk = False
    If Not SyncKardexComprasMasivo()              Then bOk = False
    If Not SyncKardexMermaCotizacionMasivo()      Then bOk = False
    KdxLog "MasivoTienda", IIf(bOk, "OK", "Con errores") & " - " & Now()
    MsgBox "Masivo Tienda " & IIf(bOk, "OK.", "con errores (ver log)."), _
           IIf(bOk, vbInformation, vbExclamation), "Sync Kardex Masivo Tienda"
    SyncKardexTiendaMasivoCompleto = bOk
End Function

' ── Master masivo CENTRAL (codigoLocal()=0) ──────────────
' Tablas: Compras, PreIngresoPitaya, SubPreIngresosPitaya
' Activar desde botón panel admin en el sistema central.
Public Function SyncKardexCentralMasivoCompleto() As Boolean
    Dim bOk As Boolean : bOk = True
    KdxLog "MasivoCentral", "Iniciando masivo central - " & Now()
    If Not SyncKardexComprasMasivo()          Then bOk = False
    If Not SyncKardexPreIngresosMasivo()      Then bOk = False
    If Not SyncKardexSubPreIngresosMasivo()   Then bOk = False
    KdxLog "MasivoCentral", IIf(bOk, "OK", "Con errores") & " - " & Now()
    MsgBox "Masivo Central " & IIf(bOk, "OK.", "con errores (ver log)."), _
           IIf(bOk, vbInformation, vbExclamation), "Sync Kardex Masivo Central"
    SyncKardexCentralMasivoCompleto = bOk
End Function

' ══════════════════════════════════════════════════════════
' MOTOR GENÉRICO: Limpiar + Enviar en batches de 200
' ══════════════════════════════════════════════════════════
Private Function KdxSyncTabla(sUrl As String, sSQL As String, _
    sModoLimpiar As String, sTablaId As String, codSuc As String) As Boolean
    On Error GoTo EH

    Dim sResp As String

    ' Paso 1: limpiar el rango en el host
    If Not KdxLimpiar(sUrl, codSuc, sModoLimpiar, sResp) Then
        KdxLog sTablaId, "Limpiar fallo: " & Left(sResp, 200)
        KdxSyncTabla = False : Exit Function
    End If
    KdxLog sTablaId, "Limpiar OK (" & sModoLimpiar & ")"

    ' Paso 2: leer local y enviar en bloques de 200
    Dim db As DAO.Database : Set db = CurrentDb()
    Dim rs As DAO.Recordset
    Set rs = db.OpenRecordset(sSQL, dbOpenForwardOnly, dbReadOnly)

    If rs.EOF And rs.BOF Then
        rs.Close : Set rs = Nothing : Set db = Nothing
        KdxLog sTablaId, "Sin registros locales para enviar."
        KdxSyncTabla = True : Exit Function
    End If

    Dim bOk    As Boolean : bOk    = True
    Dim sRows  As String  : sRows  = "["
    Dim bFirst As Boolean : bFirst = True
    Dim nCount As Long    : nCount = 0
    Dim nTotal As Long    : nTotal = 0

    Do While Not rs.EOF
        If Not bFirst Then sRows = sRows & ","
        bFirst = False
        sRows  = sRows & KdxBuildRow(rs, codSuc, sTablaId)
        nCount = nCount + 1
        nTotal = nTotal + 1

        If nCount >= KDX_BATCH_SIZE Then
            sRows = sRows & "]"
            If Not KdxEnviarBatch(sUrl, codSuc, sRows, sResp) Then bOk = False
            sRows = "[" : bFirst = True : nCount = 0
        End If
        rs.MoveNext
    Loop

    ' Enviar último bloque parcial
    If nCount > 0 Then
        sRows = sRows & "]"
        If Not KdxEnviarBatch(sUrl, codSuc, sRows, sResp) Then bOk = False
    End If

    rs.Close : Set rs = Nothing : Set db = Nothing
    KdxLog sTablaId, "Fin. Total=" & nTotal & " | " & IIf(bOk, "OK", "Con errores")
    KdxSyncTabla = bOk
    Exit Function
EH:
    KdxLog sTablaId, "Error " & Err.Number & ": " & Err.Description
    On Error Resume Next
    If Not rs Is Nothing Then rs.Close
    Set rs = Nothing : Set db = Nothing
    On Error GoTo 0
    KdxSyncTabla = False
End Function

' ── Despachar al builder correcto ────────────────────────
Private Function KdxBuildRow(rs As DAO.Recordset, codSuc As String, tid As String) As String
    Select Case tid
        Case "IC"  : KdxBuildRow = KdxRowIC(rs, codSuc)
        Case "AI"  : KdxBuildRow = KdxRowAI(rs, codSuc)
        Case "C"   : KdxBuildRow = KdxRowC(rs, codSuc)
        Case "MC"  : KdxBuildRow = KdxRowMC(rs, codSuc)
        Case "PI"  : KdxBuildRow = KdxRowPI(rs, codSuc)
        Case "SPI" : KdxBuildRow = KdxRowSPI(rs, codSuc)
        Case Else  : KdxBuildRow = "{}"
    End Select
End Function

' ══════════════════════════════════════════════════════════
' BUILDERS DE FILA JSON — uno por tabla
' ══════════════════════════════════════════════════════════

Private Function KdxRowIC(rs As DAO.Recordset, c As String) As String
    On Error Resume Next
    Dim s As String : s = "{"
    s = s & """CodICotizacion"":"  & KdxVal(rs!CodICotizacion)  & ","
    s = s & """CodCotizacion"":"   & KdxVal(rs!CodCotizacion)   & ","
    s = s & """Cantidad"":"        & KdxVal(rs!Cantidad)         & ","
    s = s & """Fecha"":"           & KdxFecha(rs!Fecha)          & ","
    s = s & """lista"":"           & KdxVal(rs!lista)            & ","
    s = s & """CodOperario"":"     & KdxVal(rs!CodOperario)      & ","
    s = s & """primerenvio"":"     & KdxVal(rs!primerenvio)      & ","
    s = s & """segundoenvio"":"    & KdxVal(rs!segundoenvio)     & ","
    s = s & """cantidadunidad"":"  & KdxVal(rs!cantidadunidad)   & ","
    s = s & """cantidadpaquete"":" & KdxVal(rs!cantidadpaquete)  & ","
    s = s & """Sucursal"":"        & KdxStr(c)
    s = s & "}"
    On Error GoTo 0 : KdxRowIC = s
End Function

Private Function KdxRowAI(rs As DAO.Recordset, c As String) As String
    On Error Resume Next
    Dim s As String : s = "{"
    s = s & """CodAjustesInventario"":" & KdxVal(rs!CodAjustesInventario) & ","
    s = s & """CodCotizacion"":"        & KdxVal(rs!CodCotizacion)        & ","
    s = s & """Cantidad"":"             & KdxVal(rs!Cantidad)              & ","
    s = s & """Fecha"":"                & KdxFecha(rs!Fecha)               & ","
    s = s & """Observacion"":"          & KdxStr(rs!Observacion)           & ","
    s = s & """Sucursal"":"             & KdxStr(c)
    s = s & "}"
    On Error GoTo 0 : KdxRowAI = s
End Function

Private Function KdxRowC(rs As DAO.Recordset, c As String) As String
    On Error Resume Next
    Dim s As String : s = "{"
    s = s & """CodIngresoAlmacen"":" & KdxVal(rs!CodIngresoAlmacen) & ","
    s = s & """CodCotizacion"":"     & KdxVal(rs!CodCotizacion)      & ","
    s = s & """Cantidad"":"          & KdxVal(rs!Cantidad)            & ","
    s = s & """Fecha"":"             & KdxFecha(rs!Fecha)             & ","
    s = s & """CostoTotal"":"        & KdxVal(rs!CostoTotal)          & ","
    s = s & """Observaciones"":"     & KdxStr(rs!Observaciones)       & ","
    s = s & """CodProveedor"":"      & KdxVal(rs!CodProveedor)        & ","
    s = s & """Destino"":"           & KdxStr(rs!Destino)             & ","
    s = s & """Tipo"":"              & KdxStr(rs!Tipo)                & ","
    s = s & """Pagado"":"            & KdxFecha(rs!Pagado)            & ","
    s = s & """NumeroFactura"":"     & KdxStr(rs!NumeroFactura)       & ","
    s = s & """CodOperario"":"       & KdxVal(rs!CodOperario)         & ","
    s = s & """Ingresado"":"         & KdxVal(rs!Ingresado)           & ","
    s = s & """Lote"":"              & KdxVal(rs!Lote)                & ","
    s = s & """Peso"":"              & KdxVal(rs!Peso)                & ","
    s = s & """Sucursal"":"          & KdxStr(c)
    s = s & "}"
    On Error GoTo 0 : KdxRowC = s
End Function

Private Function KdxRowMC(rs As DAO.Recordset, c As String) As String
    On Error Resume Next
    Dim s As String : s = "{"
    s = s & """CodMermaUnidad"":"  & KdxVal(rs!CodMermaUnidad) & ","
    s = s & """CodCotizacion"":"   & KdxVal(rs!CodCotizacion)  & ","
    s = s & """Cantidad"":"        & KdxVal(rs!Cantidad)        & ","
    s = s & """Fecha"":"           & KdxFecha(rs!Fecha)         & ","
    s = s & """Observacion"":"     & KdxStr(rs!Observacion)     & ","
    s = s & """CodIncidencia"":"   & KdxVal(rs!CodIncidencia)   & ","
    s = s & """Operario"":"        & KdxVal(rs!Operario)        & ","
    s = s & """Sucursal"":"        & KdxStr(c)
    s = s & "}"
    On Error GoTo 0 : KdxRowMC = s
End Function

Private Function KdxRowPI(rs As DAO.Recordset, c As String) As String
    On Error Resume Next
    Dim s As String : s = "{"
    s = s & """CodPreIngresoPitaya"":" & KdxVal(rs!CodPreIngresoPitaya) & ","
    s = s & """Fecha"":"               & KdxFecha(rs!Fecha)              & ","
    s = s & """Hora"":"                & KdxHora(rs!Hora)                & ","
    s = s & """Destino"":"             & KdxStr(rs!Destino)              & ","
    s = s & """Validado"":"            & KdxVal(rs!Validado)             & ","
    s = s & """Impreso"":"             & KdxVal(rs!Impreso)              & ","
    s = s & """Sucursal"":"            & KdxStr(c)
    s = s & "}"
    On Error GoTo 0 : KdxRowPI = s
End Function

Private Function KdxRowSPI(rs As DAO.Recordset, c As String) As String
    On Error Resume Next
    Dim s As String : s = "{"
    s = s & """CodSubPreIngresoPitaya"":" & KdxVal(rs!CodSubPreIngresoPitaya) & ","
    s = s & """CodCotizacion"":"          & KdxVal(rs!CodCotizacion)           & ","
    s = s & """Cantidad"":"               & KdxVal(rs!Cantidad)                 & ","
    s = s & """CodPreIngresoPitaya"":"    & KdxVal(rs!CodPreIngresoPitaya)      & ","
    s = s & """alerta"":"                 & KdxVal(rs!alerta)                   & ","
    s = s & """Sucursal"":"               & KdxStr(c)
    s = s & "}"
    On Error GoTo 0 : KdxRowSPI = s
End Function

' ══════════════════════════════════════════════════════════
' HTTP HELPERS
' ══════════════════════════════════════════════════════════

' Llamada de limpieza (limpiar_30dias o limpiar_total)
Private Function KdxLimpiar(sUrl As String, codSuc As String, _
    sModo As String, ByRef sRespOut As String) As Boolean
    Dim sPayload As String
    sPayload = "{""sucursal"":" & codSuc & ",""modo"":""" & sModo & """}"
    KdxLimpiar = KdxHttpPost(sUrl, sPayload, sRespOut)
End Function

' Enviar un bloque de filas (modo=insertar)
Private Function KdxEnviarBatch(sUrl As String, codSuc As String, _
    sRows As String, ByRef sRespOut As String) As Boolean
    Dim sPayload As String
    sPayload = "{""sucursal"":" & codSuc & ",""modo"":""insertar"",""rows"":" & sRows & "}"
    KdxEnviarBatch = KdxHttpPost(sUrl, sPayload, sRespOut)
End Function

' POST HTTP genérico
Private Function KdxHttpPost(sUrl As String, sPayload As String, _
    ByRef sRespOut As String) As Boolean
    On Error GoTo EH
    Dim http As Object
    Set http = CreateObject("MSXML2.ServerXMLHTTP.6.0")
    http.Open "POST", sUrl, False
    http.setRequestHeader "Content-Type", "application/json; charset=utf-8"
    http.setRequestHeader "Authorization", "Bearer " & KDX_TOKEN
    http.setRequestHeader "User-Agent", "PitayaAccess/1.0"
    http.setTimeouts 5000, 5000, 30000, 30000
    http.Send sPayload
    sRespOut = http.responseText
    KdxHttpPost = (http.Status = 200 And InStr(sRespOut, """success"":true") > 0)
    Set http = Nothing
    Exit Function
EH:
    KdxLog "HttpPost", "Error " & Err.Number & ": " & Err.Description
    Set http = Nothing
    KdxHttpPost = False
End Function

' ══════════════════════════════════════════════════════════
' JSON HELPERS
' ══════════════════════════════════════════════════════════

Private Function KdxVal(v As Variant) As String
    If IsNull(v) Or IsEmpty(v) Then
        KdxVal = "null"
    ElseIf IsNumeric(v) Then
        KdxVal = Replace(CStr(CDbl(v)), ",", ".")
    Else
        KdxVal = """" & KdxEsc(CStr(v)) & """"
    End If
End Function

Private Function KdxStr(v As Variant) As String
    If IsNull(v) Or IsEmpty(v) Or CStr(v) = "" Then
        KdxStr = "null"
    Else
        KdxStr = """" & KdxEsc(CStr(v)) & """"
    End If
End Function

Private Function KdxFecha(v As Variant) As String
    On Error Resume Next
    If IsNull(v) Or IsEmpty(v) Then KdxFecha = "null" : Exit Function
    Dim s As String
    s = Format(CDate(v), "yyyy-mm-dd")
    If Err.Number <> 0 Or s = "" Then KdxFecha = "null" : Err.Clear : Exit Function
    KdxFecha = """" & s & """"
    On Error GoTo 0
End Function

Private Function KdxHora(v As Variant) As String
    On Error Resume Next
    If IsNull(v) Or IsEmpty(v) Then KdxHora = "null" : Exit Function
    Dim s As String
    s = Format(CDate(v), "HH:nn:ss")
    If Err.Number <> 0 Or s = "" Then KdxHora = "null" : Err.Clear : Exit Function
    KdxHora = """" & s & """"
    On Error GoTo 0
End Function

Private Function KdxEsc(s As String) As String
    s = Replace(s, "\", "\\")
    s = Replace(s, """", "\""")
    s = Replace(s, Chr(10), "\n")
    s = Replace(s, Chr(13), "\r")
    s = Replace(s, Chr(9), "\t")
    KdxEsc = s
End Function

' ══════════════════════════════════════════════════════════
' LOG (Debug.Print + tabla KardexSyncLog si existe)
' ══════════════════════════════════════════════════════════
Private Sub KdxLog(sOrigen As String, sMensaje As String)
    Debug.Print "[SyncKardex][" & sOrigen & "] " & sMensaje

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
