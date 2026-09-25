Option Compare Database

Public Function AplicarCuponSimple(ByVal numeroCupon As String, _
                                  Optional ByVal codSucursal As Long = 0, _
                                  Optional ByVal CodPedido As Long = 0) As Boolean
    'On Error GoTo ErrorHandler
    
    Dim http As Object
    Dim responseText As String
    Dim url As String
    
    Const API_URL As String = "https://proxy.batidospitaya.com/api/aplicar_cupon.php"
    Const API_TOKEN As String = "a8f5e2d9c4b7a1e6f3d8c5b2a9e6d3f0c7a4b1e8d5c2a9f6e3d0c7b4a1e8f5d2"
    
    ' Validar entrada
    If Len(Trim(numeroCupon)) = 0 Then
        MsgBox "Debe proporcionar un número de cupón", vbExclamation
        AplicarCuponSimple = False
        Exit Function
    End If
    
    ' Construir URL con parámetros
    url = API_URL & "?token=" & API_TOKEN & "&numero_cupon=" & URLEncode(numeroCupon)
    
    ' Agregar parámetros solo si son mayores que 0
    If codSucursal > 0 Then
        url = url & "&cod_sucursal=" & CStr(codSucursal)
    End If
    
    If CodPedido > 0 Then
        url = url & "&cod_pedido=" & CStr(CodPedido)
    End If
    
    ' NO enviar hora_activacion - el servidor usará la hora actual automáticamente
    
    ' Enviar solicitud GET
    Set http = CreateObject("MSXML2.ServerXMLHTTP.6.0")
    http.Open "GET", url, False
    http.setRequestHeader "Authorization", "Bearer " & API_TOKEN
    http.send
    
    responseText = http.responseText
    
    ' Verificar respuesta
    If http.Status = 200 Then
        If ExtraerValorJSON(responseText, "success") = "true" Then
            AplicarCuponSimple = True
        Else
            Dim errorMsg As String
            errorMsg = ExtraerValorJSON(responseText, "error")
            
            ' Mensajes específicos
            If InStr(1, errorMsg, "no encontrado", vbTextCompare) > 0 Then
                MsgBox "Cupón '" & numeroCupon & "' no encontrado", vbExclamation
            Else
                MsgBox "Error: " & errorMsg, vbCritical
            End If
            
            AplicarCuponSimple = False
        End If
    Else
        Select Case http.Status
            Case 400
                MsgBox "Solicitud incorrecta", vbCritical
            Case 401
                MsgBox "Error de autenticación", vbCritical
            Case 404
                MsgBox "Cupón no encontrado", vbCritical
            Case 500
                MsgBox "Error interno del servidor", vbCritical
            Case Else
                MsgBox "Error " & http.Status & ": " & http.StatusText, vbCritical
        End Select
        
        AplicarCuponSimple = False
    End If
    
    Set http = Nothing
    Exit Function
    
ErrorHandler:
    MsgBox "Error aplicando cupón: " & Err.Description, vbCritical
    AplicarCuponSimple = False
    Set http = Nothing
End Function

