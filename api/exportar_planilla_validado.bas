Option Explicit

' ========== CONFIGURACIÓN ==========
Const API_URL = "https://api.batidospitaya.com/api/api_boleta.php"
Const API_TOKEN = "a8f5e2d9c4b7a1e6f3d8c5b2a9e6d3f0c7a4b1e8d5c2a9f6e3d0c7b4a1e8f5d2"

' ========== EXPORTAR PLANILLA CON VALIDACIÓN ==========
Sub ExportarPlanillaValidado()
    On Error GoTo ErrorHandler
    
    Dim ws As Worksheet
    Dim ultimaFila As Long
    Dim i As Long
    Dim totalRegistros As Long
    Dim registrosOmitidos As Long
    Dim jsonCompleto As String
    Dim response As String
    Dim inicioTiempo As Double
    
    ' Iniciar temporizador
    inicioTiempo = Timer
    
    ' Referencia a la hoja BASICO
    Set ws = ActiveWorkbook.Sheets("BASICO")
    ultimaFila = ws.Cells(ws.Rows.count, "O").End(xlUp).Row
    
    ' Validar que hay datos
    If ultimaFila < 11 Then
        MsgBox "No hay datos para procesar (desde fila 11).", vbExclamation, "Sin Datos"
        Exit Sub
    End If
    
    ' Desactivar actualizaciones de pantalla
    Application.ScreenUpdating = False
    Application.Calculation = xlCalculationManual
    
    ' Construir JSON
    jsonCompleto = "["
    totalRegistros = 0
    registrosOmitidos = 0
    
    For i = 11 To ultimaFila
        ' Validar campos requeridos
        If ValidarFila(ws, i) Then
            If totalRegistros > 0 Then
                jsonCompleto = jsonCompleto & ","
            End If
            
            ' Construir objeto JSON
            jsonCompleto = jsonCompleto & "{"
            jsonCompleto = jsonCompleto & """cod_operario"":" & Chr(34) & ws.Cells(i, "O").Value & Chr(34) & ","
            jsonCompleto = jsonCompleto & """empleado_nombre"":" & Chr(34) & Replace(ws.Cells(i, "P").Value, Chr(34), "'") & Chr(34) & ","
            jsonCompleto = jsonCompleto & """salario_basico"":" & Val(ws.Cells(i, "Q").Value) & ","
            jsonCompleto = jsonCompleto & """fecha_planilla"":" & Chr(34) & Format(ws.Cells(i, "R").Value, "yyyy-mm-dd") & Chr(34) & ","
            jsonCompleto = jsonCompleto & """salario_quincenal_dias"":" & Val(ws.Cells(i, "S").Value) & ","
            jsonCompleto = jsonCompleto & """salario_quincenal_monto"":" & Val(ws.Cells(i, "T").Value) & ","
            jsonCompleto = jsonCompleto & """feriados_horas"":" & Val(ws.Cells(i, "U").Value) & ","
            jsonCompleto = jsonCompleto & """feriados_monto"":" & Val(ws.Cells(i, "V").Value) & ","
            jsonCompleto = jsonCompleto & """extras_horas"":" & Val(ws.Cells(i, "W").Value) & ","
            jsonCompleto = jsonCompleto & """extras_monto"":" & Val(ws.Cells(i, "X").Value) & ","
            jsonCompleto = jsonCompleto & """faltas_dias"":" & Val(ws.Cells(i, "Y").Value) & ","
            jsonCompleto = jsonCompleto & """faltas_monto"":" & Val(ws.Cells(i, "Z").Value) & ","
            jsonCompleto = jsonCompleto & """inss_porcentaje"":" & Val(ws.Cells(i, "AA").Value) & ","
            jsonCompleto = jsonCompleto & """inss_monto"":" & Val(ws.Cells(i, "AB").Value) & ","
            jsonCompleto = jsonCompleto & """vacaciones_dias"":" & Val(ws.Cells(i, "AC").Value) & ","
            jsonCompleto = jsonCompleto & """Deducciones"":" & Val(ws.Cells(i, "AD").Value)
            jsonCompleto = jsonCompleto & "}"
            
            totalRegistros = totalRegistros + 1
        Else
            registrosOmitidos = registrosOmitidos + 1
        End If
    Next i
    
    jsonCompleto = jsonCompleto & "]"
    
    ' Validar que hay registros
    If totalRegistros = 0 Then
        MsgBox "No hay registros válidos para enviar.", vbExclamation, "Sin Datos"
        GoTo Cleanup
    End If
    
    ' Mostrar progreso
    Application.StatusBar = "Enviando " & totalRegistros & " registros..."
    DoEvents
    
    ' Enviar a API
    response = EnviarAPI(jsonCompleto)
    
    ' Calcular tiempo
    Dim tiempo As Double
    tiempo = Round(Timer - inicioTiempo, 2)
    
    ' Mostrar resultado
    If InStr(response, "success") > 0 Then
        MsgBox "? PROCESO COMPLETADO" & vbCrLf & _
               "Registros enviados: " & totalRegistros & vbCrLf & _
               IIf(registrosOmitidos > 0, "Registros omitidos: " & registrosOmitidos & vbCrLf, "") & _
               "Tiempo: " & tiempo & " segundos", _
               vbInformation, "Éxito"
    Else
        MsgBox "? ERROR EN EL ENVÍO" & vbCrLf & _
               "Respuesta: " & Left(response, 150) & vbCrLf & _
               "Tiempo: " & tiempo & " segundos", _
               vbCritical, "Error"
    End If
    
Cleanup:
    ' Restaurar configuración
    Application.ScreenUpdating = True
    Application.Calculation = xlCalculationAutomatic
    Application.StatusBar = False
    Exit Sub
    
ErrorHandler:
    MsgBox "Error: " & Err.Description, vbCritical
    Resume Cleanup
End Sub

' ========== VALIDAR FILA ==========
Private Function ValidarFila(ws As Worksheet, fila As Long) As Boolean
    ValidarFila = False
    
    ' Validar campos obligatorios
    If Trim(ws.Cells(fila, "O").Value) = "" Then Exit Function
    If Trim(ws.Cells(fila, "P").Value) = "" Then Exit Function
    If Not IsNumeric(ws.Cells(fila, "Q").Value) Then Exit Function
    If Not IsDate(ws.Cells(fila, "R").Value) Then Exit Function
    
    ValidarFila = True
End Function

' ========== ENVIAR DATOS A API ==========
Private Function EnviarAPI(jsonData As String) As String
    On Error Resume Next
    
    Dim http As Object
    Dim postData As String
    
    ' Preparar datos POST
    postData = "token=" & API_TOKEN & "&datos=" & jsonData
    
    ' Crear objeto HTTP
    Set http = CreateObject("MSXML2.XMLHTTP.6.0")
    If http Is Nothing Then
        Set http = CreateObject("MSXML2.XMLHTTP.3.0")
    End If
    If http Is Nothing Then
        Set http = CreateObject("MSXML2.XMLHTTP")
    End If
    
    ' Verificar que se creó el objeto
    If http Is Nothing Then
        EnviarAPI = "Error: No se pudo crear objeto HTTP"
        Exit Function
    End If
    
    ' Enviar petición
    http.Open "POST", API_URL, False
    http.setRequestHeader "Content-Type", "application/x-www-form-urlencoded"
    http.send postData
    
    ' Obtener respuesta
    EnviarAPI = http.responseText
    
    Set http = Nothing
End Function

