Public Function DescargarTablaCompleta(ByVal nombreTablaOrigen As String, ByVal nombreTablaDestino As String, Optional ByVal Filtro As String = "") As Boolean
    On Error GoTo ErrorHandler
    ' elimina la tabla si existe
    
    Dim http As Object
    Dim responseText As String
    Dim url As String
    
    Const API_URL As String = "https://proxy.batidospitaya.com/api/descarga_tabla.php"
    Const API_TOKEN As String = "a8f5e2d9c4b7a1e6f3d8c5b2a9e6d3f0c7a4b1e8d5c2a9f6e3d0c7b4a1e8f5d2"
    
    ' Construir URL
    url = API_URL & "?tabla=" & nombreTablaOrigen & "&token=" & API_TOKEN
    
    ' Agregar filtro si existe
    If Len(Trim(Filtro)) > 0 Then
        url = url & "&filtro=" & URLEncode(Filtro)
    End If
    
    ' Crear objeto HTTP
    Set http = CreateObject("MSXML2.ServerXMLHTTP.6.0")
    http.Open "GET", url, False
    http.setRequestHeader "Authorization", "Bearer " & API_TOKEN
    http.setRequestHeader "Content-Type", "application/json"
    http.send
    
    If http.Status = 200 Then
        responseText = http.responseText
        
        If ParseYCrearTabla(responseText, nombreTablaDestino) Then
            DescargarTablaCompleta = True
            'MsgBox "Tabla descargada exitosamente", vbInformation
        Else
            DescargarTablaCompleta = False
        End If
    Else
        MsgBox "Error HTTP: " & http.Status & " - " & http.StatusText, vbCritical
        DescargarTablaCompleta = False
    End If
    
    Set http = Nothing
    Exit Function
    
ErrorHandler:
    MsgBox "Error: " & Err.Description, vbCritical
    DescargarTablaCompleta = False
    'cuando no carga por problema de conexion  usa Call importartablaespecifica("Main_DB", "DBIngredientes", "DBIngredientes", 1)

End Function