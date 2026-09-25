

Public Function APIDisponible() As Boolean
    ' Función ultra rápida para verificar API antes de descargar
    On Error GoTo ErrorHandler
    
    Dim http As Object
    Dim startTime As Double
    Dim url As String
    
    Const API_TEST_URL As String = "https://proxy.batidospitaya.com/api/MS_Access/ping/ping.php"
    
    startTime = Timer
    
    Set http = CreateObject("MSXML2.ServerXMLHTTP.6.0")
    http.Open "GET", API_TEST_URL, False
    http.setTimeouts 3000, 3000, 3000, 3000 ' Solo 3 segundos máximo
    
    http.send
    
    ' Verificar que responda en menos de 2 segundos y no sea Cloudflare
    If (Timer - startTime) < 2 And http.Status = 200 Then
        If Not EsRespuestaCloudflare(http.responseText) Then
            APIDisponible = True
        Else
            APIDisponible = False
        End If
    Else
        APIDisponible = False
    End If
    
    Set http = Nothing
    Exit Function
    
ErrorHandler:
    APIDisponible = False
End Function


Public Function EsRespuestaCloudflare(ByVal respuesta As String) As Boolean
    ' Verifica si la respuesta contiene indicadores de Cloudflare
    If Len(respuesta) = 0 Then
        EsRespuestaCloudflare = False
        Exit Function
    End If
    
    ' Patrones típicos de Cloudflare
    Dim patrones() As String
    patrones = Split("cloudflare,challenge-form,cf-browser-verification,Checking your browser, ray id", ",")
    
    Dim I As Integer
    For I = 0 To UBound(patrones)
        If InStr(1, respuesta, patrones(I), vbTextCompare) > 0 Then
            EsRespuestaCloudflare = True
            Exit Function
        End If
    Next I
    
    ' Verificar si es HTML en lugar de JSON
    If Left(Trim(respuesta), 1) = "<" And InStr(respuesta, "<!DOCTYPE") > 0 Then
        EsRespuestaCloudflare = True
        Exit Function
    End If
    
    ' Verificar si contiene estructura JSON básica
    If InStr(respuesta, "{") = 0 And InStr(respuesta, "[") = 0 Then
        EsRespuestaCloudflare = True
    Else
        EsRespuestaCloudflare = False
    End If
End Function
