
''''''''''''''''''''''''''''EXTRAER VALORES DE MEMEBRESIA EN HOST ''''''''''''''''''''''''''''''''''''''''

' Función principal que retorna array con datos del cliente club
' Parámetros: membresia (número de membresía), sucursal (código de sucursal)
' Retorna: Array(existe, membresia, nombre, puntos, puntos_iniciales)
Public Function DatosClubHost(ByVal membresia As Long, ByVal Sucursal As Long) As Variant
    On Error GoTo ErrorHandler
    
    Dim http As Object
    Dim responseText As String
    Dim url As String
    
    Const API_URL As String = "https://proxy.batidospitaya.com/api/consulta_cliente_club.php"
    Const API_TOKEN As String = "a8f5e2d9c4b7a1e6f3d8c5b2a9e6d3f0c7a4b1e8d5c2a9f6e3d0c7b4a1e8f5d2"
    
    ' Construir URL con parámetros
    url = API_URL & "?membresia=" & membresia & "&sucursal=" & Sucursal & "&token=" & API_TOKEN
    
    ' Crear objeto HTTP
    Set http = CreateObject("MSXML2.ServerXMLHTTP.6.0")
    http.Open "GET", url, False
    http.setRequestHeader "Authorization", "Bearer " & API_TOKEN
    http.setRequestHeader "Content-Type", "application/json"
    http.send
    
    If http.Status = 200 Then
        responseText = http.responseText
        
        ' Parsear respuesta y retornar array directamente
        DatosClubHost = ParsearRespuestaCliente(responseText)
    Else
        ' Error HTTP - retornar array con valores por defecto
        MsgBox "Error HTTP: " & http.Status & " - " & http.StatusText, vbCritical
        DatosClubHost = Array(0, 0, "", 0, 0)
    End If
    
    Set http = Nothing
    Exit Function
    
ErrorHandler:
    ' En caso de error, retornar array con valores por defecto
    'MsgBox "Error en DatosClubHost: " & Err.Description, vbCritical
    DatosClubHost = Array(0, 0, "", 0, 0)
End Function

' Función auxiliar para parsear la respuesta JSON del API
Private Function ParsearRespuestaCliente(ByVal jsonText As String) As Variant
    Dim existe As Long
    Dim membresia As Long
    Dim Nombre As String
    Dim Puntos As Double
    Dim puntosIniciales As Long
    
    ' Verificar si la operación fue exitosa
    If InStr(1, jsonText, """success"":true", vbTextCompare) = 0 Then
        ' Error en la respuesta
        ParsearRespuestaCliente = Array(0, 0, "", 0, 0)
        Exit Function
    End If
    
    ' Extraer valor de "existe"
    existe = CLng(ExtraerValorJSON(jsonText, "existe"))
    
    If existe = 0 Then
        ' Cliente no existe
        ParsearRespuestaCliente = Array(0, 0, "", 0, 0)
    Else
        ' Cliente existe, extraer datos del objeto "datos"
        Dim datosStart As Long, datosEnd As Long, datosText As String
        datosStart = InStr(1, jsonText, """datos"":{", vbTextCompare)
        
        If datosStart > 0 Then
            datosStart = datosStart + 8
            datosEnd = InStr(datosStart, jsonText, "}", vbTextCompare)
            datosText = Mid(jsonText, datosStart, datosEnd - datosStart + 1)
            
            ' Extraer membresía
            membresia = CLng(ExtraerValorJSON(datosText, "membresia"))
            
            ' Extraer nombre
            Nombre = ExtraerValorJSON(datosText, "nombre")
            
            ' Extraer puntos
            Dim puntosStr As String
            puntosStr = ExtraerValorJSON(datosText, "puntos")
            If IsNumeric(puntosStr) Then
                Puntos = CDbl(puntosStr)
            Else
                Puntos = 0
            End If
            
            ' Extraer puntos iniciales
            Dim puntosInicialesStr As String
            puntosInicialesStr = ExtraerValorJSON(datosText, "puntos_iniciales")
            If IsNumeric(puntosInicialesStr) Then
                puntosIniciales = CLng(puntosInicialesStr)
            Else
                puntosIniciales = 0
            End If
            
            ' Retornar array con datos
            ParsearRespuestaCliente = Array(1, membresia, Nombre, Puntos, puntosIniciales)
        Else
            ' No se encontró el objeto datos
            ParsearRespuestaCliente = Array(0, 0, "", 0, 0)
        End If
    End If
End Function

' Función auxiliar para extraer valores de JSON simple
Private Function ExtraerValorJSON(ByVal jsonText As String, ByVal campo As String) As String
    Dim campoPos As Long, valorStart As Long, valorEnd As Long, Valor As String
    
    campoPos = InStr(1, jsonText, """" & campo & """:", vbTextCompare)
    If campoPos > 0 Then
        valorStart = campoPos + Len(campo) + 3
        
        valorEnd = InStr(valorStart, jsonText, ",")
        If valorEnd = 0 Then valorEnd = InStr(valorStart, jsonText, "}")
        If valorEnd = 0 Then valorEnd = Len(jsonText) + 1
        
        Valor = Mid(jsonText, valorStart, valorEnd - valorStart)
        
        If Left(Valor, 1) = """" Then
            Valor = Mid(Valor, 2, Len(Valor) - 2)
        End If
    Else
        Valor = "null"
    End If
    
    ExtraerValorJSON = Trim(Valor)
End Function