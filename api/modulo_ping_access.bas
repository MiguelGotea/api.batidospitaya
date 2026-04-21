' =============================================================
' Módulo: modPing
' Propósito: Enviar señal de vida al servidor ERP Pitaya
'            para monitoreo de conectividad en tiempo real.
'
' INSTALACIÓN:
'   1. Abrir el sistema Access → Editor VBA (Alt+F11)
'   2. Insertar un módulo nuevo y pegar este código
'   3. En el formulario principal, llamar: IniciarPingAutomatico
'   4. Ajustar la constante CODIGO_SUCURSAL con el código real
' =============================================================

Option Explicit

' ── CONFIGURACIÓN ───────────────────────────────────────────
Private Const PING_URL        As String = "https://proxy.batidospitaya.com/api/ping.php"
Private Const VERSION_SISTEMA As String = "2.0"
Private Const INTERVALO_SEG   As Long   = 60   ' Ping cada 60 segundos
' Nota: el código de sucursal se obtiene vía codigoLocal() en tiempo de ejecución

' ── Variables internas ────────────────────────────────────
Private mTimerActivo   As Boolean
Private mUltimoPing    As Date
Private mPingsFallidos As Integer

' ══════════════════════════════════════════════════════════
'  FUNCIÓN PRINCIPAL: Enviar ping
' ══════════════════════════════════════════════════════════
Public Function EnviarPing() As Boolean
    On Error GoTo ErrorHandler
    
    Dim http      As Object
    Dim postData  As String
    Dim ipLocal   As String
    Dim modulo    As String
    Dim codSuc    As String
    
    ' Obtener código de sucursal desde la función del sistema
    codSuc  = CStr(codigoLocal())
    ipLocal = ObtenerIPLocal()
    modulo  = ObtenerModuloActivo()
    
    ' Construir parámetros POST
    postData = "sucursal="    & URLEncode(codSuc) & _
               "&pc_nombre="  & URLEncode(Environ("COMPUTERNAME")) & _
               "&pc_usuario=" & URLEncode(Environ("USERNAME")) & _
               "&ip_local="   & URLEncode(ipLocal) & _
               "&version="    & URLEncode(VERSION_SISTEMA) & _
               "&modulo="     & URLEncode(modulo)
    
    ' Crear objeto HTTP
    Set http = CreateObject("MSXML2.ServerXMLHTTP.6.0")
    
    http.Open "POST", PING_URL, False
    http.setRequestHeader "Content-Type", "application/x-www-form-urlencoded"
    http.setRequestHeader "User-Agent", "PitayaAccess/" & VERSION_SISTEMA
    http.setTimeouts 5000, 5000, 10000, 10000  ' 5s conexión, 10s respuesta
    
    http.Send postData
    
    If http.Status = 200 Then
        mUltimoPing    = Now()
        mPingsFallidos = 0
        EnviarPing = True
    Else
        mPingsFallidos = mPingsFallidos + 1
        EnviarPing = False
    End If
    
    Set http = Nothing
    Exit Function

ErrorHandler:
    mPingsFallidos = mPingsFallidos + 1
    EnviarPing = False
    Set http = Nothing
End Function

' ══════════════════════════════════════════════════════════
'  Timer automático — llama a la función OnTimer del form
' ══════════════════════════════════════════════════════════
'
'  USO EN EL FORM PRINCIPAL:
'
'  Private Sub Form_Open(Cancel As Integer)
'      IniciarPingAutomatico
'  End Sub
'
'  Private Sub Form_Timer()
'      PingTimerTick
'  End Sub
'
'  Private Sub Form_Close(Cancel As Integer)
'      DetenerPingAutomatico
'  End Sub

Public Sub IniciarPingAutomatico()
    ' Solo activar si el Access está corriendo como sistema de tienda
    If Not EsSistemaDeTienda() Then Exit Sub
    
    Dim frm As Form
    On Error Resume Next
    Set frm = Forms(0)  ' Formulario activo principal
    
    If Not frm Is Nothing Then
        frm.TimerInterval = INTERVALO_SEG * 1000  ' En milisegundos
        mTimerActivo = True
        ' Enviar ping inmediato al abrir
        EnviarPing
    End If
    On Error GoTo 0
End Sub

Public Sub DetenerPingAutomatico()
    Dim frm As Form
    On Error Resume Next
    Set frm = Forms(0)
    If Not frm Is Nothing Then
        frm.TimerInterval = 0
    End If
    mTimerActivo = False
    On Error GoTo 0
End Sub

Public Sub PingTimerTick()
    ' Llamar esto desde el evento Form_Timer del form principal
    ' Valida de nuevo por si cambia el contexto en caliente
    If EsSistemaDeTienda() Then EnviarPing
End Sub

' ════════════════════════════════════════════════════════
'  Verificar que el Access está en modo Sistema de Tienda
' ════════════════════════════════════════════════════════
Private Function EsSistemaDeTienda() As Boolean
    ' Condición 1: esModuloOpitayaRaiz() debe retornar 0
    '   (0 = modo tienda | otro valor = test/global)
    If esModuloOpitayaRaiz() <> 0 Then
        EsSistemaDeTienda = False
        Exit Function
    End If
    
    ' Condición 2: debe existir la tabla vinculada DatosSistema
    EsSistemaDeTienda = TieneTablaVinculada("DatosSistema")
End Function

Private Function TieneTablaVinculada(nombreTabla As String) As Boolean
    ' Verifica que la tabla exista Y sea de tipo vinculada (Connect <> "")
    On Error Resume Next
    Dim tdf As Object
    Set tdf = CurrentDb.TableDefs(nombreTabla)
    
    If Err.Number <> 0 Then
        ' La tabla no existe
        TieneTablaVinculada = False
        Exit Function
    End If
    
    ' Una tabla vinculada siempre tiene la propiedad Connect no vacía
    TieneTablaVinculada = (Len(tdf.Connect) > 0)
    Set tdf = Nothing
    On Error GoTo 0
End Function

' ══════════════════════════════════════════════════════════
'  Helpers
' ══════════════════════════════════════════════════════════
Private Function ObtenerIPLocal() As String
    On Error Resume Next
    Dim wsh    As Object
    Dim oNet   As Object
    Dim ip     As String
    
    Set wsh  = CreateObject("WScript.Shell")
    Set oNet = CreateObject("WScript.Network")
    
    ' Método alternativo vía WMI
    Dim objWMI As Object
    Dim colAdp  As Object
    Dim objAdp  As Object
    
    Set objWMI = GetObject("winmgmts:\\.\root\cimv2")
    Set colAdp  = objWMI.ExecQuery( _
        "SELECT * FROM Win32_NetworkAdapterConfiguration WHERE IPEnabled = True")
    
    For Each objAdp In colAdp
        If Not IsNull(objAdp.IPAddress) Then
            ip = objAdp.IPAddress(0)
            If Left(ip, 3) <> "169" Then  ' Ignorar APIPA
                Exit For
            End If
        End If
    Next
    
    ObtenerIPLocal = IIf(ip = "", "0.0.0.0", ip)
    On Error GoTo 0
End Function

Private Function ObtenerModuloActivo() As String
    On Error Resume Next
    Dim frm As Form
    ' Intentar obtener el nombre del formulario activo
    Set frm = Screen.ActiveForm
    If Not frm Is Nothing Then
        ObtenerModuloActivo = frm.Name
    Else
        ObtenerModuloActivo = "Menu Principal"
    End If
    On Error GoTo 0
End Function

Private Function URLEncode(sText As String) As String
    ' Codificación básica de URL para parámetros POST
    Dim i      As Integer
    Dim c      As String
    Dim result As String
    
    For i = 1 To Len(sText)
        c = Mid(sText, i, 1)
        Select Case Asc(c)
            Case 65 To 90, 97 To 122, 48 To 57, 45, 46, 95, 126
                result = result & c
            Case 32
                result = result & "+"
            Case Else
                result = result & "%" & Hex(Asc(c))
        End Select
    Next i
    URLEncode = result
End Function

' ══════════════════════════════════════════════════════════
'  Diagnóstico — para probar manualmente desde Access
' ══════════════════════════════════════════════════════════
Public Sub ProbarPing()
    Dim codSuc As String
    codSuc = CStr(codigoLocal())
    
    If EnviarPing() Then
        MsgBox "OK Ping enviado correctamente." & vbCrLf & _
               "Sucursal: " & codSuc & vbCrLf & _
               "PC: " & Environ("COMPUTERNAME") & vbCrLf & _
               "URL: " & PING_URL, _
               vbInformation, "Monitor Pitaya"
    Else
        MsgBox "ERROR No se pudo enviar el ping." & vbCrLf & _
               "Sucursal detectada: " & codSuc & vbCrLf & _
               "Verificar conexion a internet." & vbCrLf & _
               "URL: " & PING_URL, _
               vbExclamation, "Monitor Pitaya"
    End If
End Sub
