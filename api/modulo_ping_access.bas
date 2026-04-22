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
    ' 1. Validar contexto
    If Not EsSistemaDeTienda() Then
        Debug.Print "Ping abortado: No es entorno de tienda (Raiz=" & esModuloOpitayaRaiz() & ")"
        Exit Sub
    End If
    
    Dim frm As Form
    On Error Resume Next
    Set frm = Screen.ActiveForm
    
    If Not frm Is Nothing Then
        frm.TimerInterval = INTERVALO_SEG * 1000
        mTimerActivo = True
        ' Enviar ping inmediato
        EnviarPing
    Else
        Debug.Print "Ping abortado: No se detectó formulario activo para el Timer."
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
    Dim c1 As Boolean, c2 As Boolean
    
    ' Condición 1: esModuloOpitayaRaiz() = 0
    c1 = (esModuloOpitayaRaiz() = 0)
    
    ' Condición 2: Existe tabla DatosSistema
    c2 = TieneTablaVinculada("DatosSistema")
    
    EsSistemaDeTienda = (c1 And c2)
End Function

Function TieneTablaVinculada(nombreTabla As String) As Boolean
    On Error GoTo ErrorHandler
    
    Dim db As DAO.Database
    Dim tdf As DAO.TableDef
    
    Set db = CurrentDb
    Set tdf = db.TableDefs(nombreTabla)
    
    ' Verifica si es tabla vinculada
    ' Propiedad Connect no vacía y no es una tabla del sistema
    If tdf.Connect <> "" And Left(nombreTabla, 4) <> "MSys" Then
        TieneTablaVinculada = True
    Else
        TieneTablaVinculada = False
    End If
    
CleanExit:
    Set tdf = Nothing
    Set db = Nothing
    Exit Function
    
ErrorHandler:
    TieneTablaVinculada = False
    Resume CleanExit
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
    Dim c1 As Boolean, c2 As Boolean
    
    codSuc = CStr(codigoLocal())
    c1 = (esModuloOpitayaRaiz() = 0)
    c2 = TieneTablaVinculada("DatosSistema")
    
    If EnviarPing() Then
        MsgBox "OK - Conexión exitosa." & vbCrLf & _
               "Sucursal: " & codSuc & vbCrLf & _
               "1. Raiz=0: " & IIf(c1, "✅", "❌ (" & esModuloOpitayaRaiz() & ")") & vbCrLf & _
               "2. Tabla DatosSistema: " & IIf(c2, "✅", "❌ No existe") & vbCrLf & _
               "Modo Tienda: " & IIf(c1 And c2, "SÍ ✅", "NO ❌") & vbCrLf & _
               "URL: " & PING_URL, _
               vbInformation, "Monitor Pitaya"
    Else
        MsgBox "ERROR - No se pudo conectar." & vbCrLf & _
               "Verificar internet o URL.", _
               vbExclamation, "Monitor Pitaya"
    End If
End Sub
