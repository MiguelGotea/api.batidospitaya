Attribute VB_Name = "ModuloClientesClub"
Option Explicit

' ========== CONFIGURACIÓN ==========
Const API_BASE_URL = "https://api.batidospitaya.com/api/"
Const API_TOKEN = "a8f5e2d9c4b7a1e6f3d8c5b2a9e6d3f0c7a4b1e8d5c2a9f6e3d0c7b4a1e8f5d2"

' ========== FUNCIÓN: VERIFICAR CÉDULA CLUB ==========
' Retorna: "sin_registro" si no tiene cédula o el número de cédula si existe
Public Function verificarcedulaclub(codigoclub As Variant) As String
    On Error GoTo ErrorHandler
    
    Dim http As Object
    Dim url As String
    Dim response As String
    Dim json As Object
    
    ' Validar entrada
    If IsNull(codigoclub) Or codigoclub = "" Then
        verificarcedulaclub = "sin_registro"
        Exit Function
    End If
    
    ' Preparar URL
    url = API_BASE_URL & "verificar_cedula_club.php?token=" & API_TOKEN & "&membresia=" & codigoclub
    
    ' Crear objeto HTTP
    Set http = CreateObject("MSXML2.XMLHTTP.6.0")
    http.Open "GET", url, False
    http.send
    
    ' Obtener respuesta
    response = http.responseText
    
    ' Procesar respuesta simple (buscando el campo cedula)
    ' Nota: En Access puro sin librerías externas de JSON, hacemos un parse manual simple
    If InStr(response, """success"":true") > 0 Then
        If InStr(response, """cedula"":null") > 0 Or InStr(response, """cedula"":""""") > 0 Then
            verificarcedulaclub = "sin_registro"
        Else
            ' Extraer valor de cedula
            Dim startPos As Long, endPos As Long
            startPos = InStr(response, """cedula"":""") + 10
            If startPos > 10 Then
                endPos = InStr(startPos, response, """")
                verificarcedulaclub = Mid(response, startPos, endPos - startPos)
            Else
                verificarcedulaclub = "sin_registro"
            End If
        End If
    Else
        verificarcedulaclub = "sin_registro"
    End If
    
Cleanup:
    Set http = Nothing
    Exit Function
    
ErrorHandler:
    verificarcedulaclub = "sin_registro"
    Resume Cleanup
End Function

' ========== FUNCIÓN: GUARDAR NÚMERO CÉDULA HOST ==========
' Envía y actualiza los datos de la membresía en la base de datos
Public Function guardarnumerocedulahost(membresia As Variant, cedula As Variant) As String
    On Error GoTo ErrorHandler
    
    Dim http As Object
    Dim url As String
    Dim postData As String
    Dim response As String
    
    ' Validar entrada
    If IsNull(membresia) Or IsNull(cedula) Then
        guardarnumerocedulahost = "Error: Datos nulos"
        Exit Function
    End If
    
    ' Preparar URL y datos POST
    url = API_BASE_URL & "guardar_cedula_club.php"
    postData = "token=" & API_TOKEN & "&membresia=" & membresia & "&cedula=" & cedula
    
    ' Crear objeto HTTP
    Set http = CreateObject("MSXML2.XMLHTTP.6.0")
    http.Open "POST", url, False
    http.setRequestHeader "Content-Type", "application/x-www-form-urlencoded"
    http.send postData
    
    ' Obtener respuesta
    response = http.responseText
    
    If InStr(response, """success"":true") > 0 Then
        guardarnumerocedulahost = "OK"
    Else
        guardarnumerocedulahost = "Error: " & response
    End If
    
Cleanup:
    Set http = Nothing
    Exit Function
    
ErrorHandler:
    guardarnumerocedulahost = "Error: " & Err.Description
    Resume Cleanup
End Function
