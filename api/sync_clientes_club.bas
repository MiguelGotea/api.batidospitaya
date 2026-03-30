Attribute VB_Name = "SyncClientesClub"
Option Explicit

' ========== CONFIGURACIÓN ==========
Const API_BASE_URL = "https://api.batidospitaya.com/api/"
Const API_TOKEN = "a8f5e2d9c4b7a1e6f3d8c5b2a9e6d3f0c7a4b1e8d5c2a9f6e3d0c7b4a1e8f5d2"

' ========== SUB: SINCRONIZAR CÉDULAS LOCALES ==========
' Descarga y actualiza las cédulas del host para la sucursal actual
Public Sub SincronizarCedulasLocales()
    On Error GoTo ErrorHandler
    
    Dim http As Object
    Dim url As String
    Dim response As String
    Dim sucursal As Variant
    Dim db As DAO.Database
    Dim rs As DAO.Recordset
    Dim totalActualizados As Long
    Dim totalRecibidos As Long
    
    ' 1. Obtener sucursal local (función del sistema)
    sucursal = codigoLoca()
    
    If IsNull(sucursal) Or sucursal = "" Then
        MsgBox "No se pudo determinar el código de sucursal local.", vbExclamation, "Error de Sincronización"
        Exit Sub
    End If
    
    ' 2. Llamar API
    url = API_BASE_URL & "obtener_cedulas_sucursal.php?token=" & API_TOKEN & "&sucursal=" & sucursal
    
    Set http = CreateObject("MSXML2.XMLHTTP.6.0")
    http.Open "GET", url, False
    http.send
    
    response = http.responseText
    
    ' 3. Procesar respuesta
    If InStr(response, """success"":true") = 0 Then
        MsgBox "Error en la respuesta del servidor: " & response, vbCritical, "Error de API"
        GoTo Cleanup
    End If
    
    ' 4. Parsear JSON simple (Array de objetos)
    ' Ejemplo: {"datos":[{"membresia":123,"cedula":"123456"},{"membresia":456,"cedula":"789012"}]}
    
    totalActualizados = 0
    totalRecibidos = 0
    
    Set db = CurrentDb
    
    ' Extraer la parte de "datos":[...]
    Dim datosPart As String
    Dim startPos As Long, endPos As Long
    startPos = InStr(response, """datos"":[") + 8
    endPos = InStrRev(response, "]")
    
    If startPos > 8 And endPos > startPos Then
        datosPart = Mid(response, startPos, endPos - startPos)
        
        ' Dividir por objetos {}
        Dim objetos() As String
        objetos = Split(datosPart, "},{")
        
        Dim obj As Variant
        Dim membresia As String
        Dim cedula As String
        
        For Each obj In objetos
            ' Limpiar llaves si existen
            Dim cleanObj As String
            cleanObj = Replace(Replace(obj, "{", ""), "}", "")
            
            ' Extraer membresia
            membresia = ExtraerValorJSON(cleanObj, "membresia")
            ' Extraer cedula
            cedula = ExtraerValorJSON(cleanObj, "cedula")
            
            If membresia <> "" And cedula <> "" Then
                totalRecibidos = totalRecibidos + 1
                
                ' Actualizar tabla local ClientesClub
                ' Usamos CodCliente (Access) que corresponde a membresia (Host)
                db.Execute "UPDATE ClientesClub SET Cedula = '" & Replace(cedula, "'", "''") & "' " & _
                           "WHERE CodCliente = " & membresia, dbFailOnError
                
                If db.RecordsAffected > 0 Then
                    totalActualizados = totalActualizados + 1
                End If
            End If
        Next obj
    End If
    
    MsgBox "Sincronización completada." & vbCrLf & _
           "Sucursal: " & sucursal & vbCrLf & _
           "Registros recibidos: " & totalRecibidos & vbCrLf & _
           "Localmente actualizados: " & totalActualizados, vbInformation, "Éxito"

Cleanup:
    Set http = Nothing
    Set rs = Nothing
    Set db = Nothing
    Exit Sub
    
ErrorHandler:
    MsgBox "Error durante la sincronización: " & Err.Description, vbCritical, "Error Crítico"
    Resume Cleanup
End Sub

' Función auxiliar para extraer valores de un objeto JSON simple "key":value o "key":"value"
Private Function ExtraerValorJSON(jsonStr As String, key As String) As String
    Dim keySearch As String
    Dim p1 As Long, p2 As Long
    
    keySearch = """" & key & """:"
    p1 = InStr(jsonStr, keySearch)
    
    If p1 > 0 Then
        p1 = p1 + Len(keySearch)
        ' Verificar si el valor empieza con comilla
        If Mid(jsonStr, p1, 1) = """" Then
            p1 = p1 + 1
            p2 = InStr(p1, jsonStr, """")
        Else
            ' Es numérico o null, buscar coma o fin
            p2 = InStr(p1, jsonStr, ",")
            If p2 = 0 Then p2 = Len(jsonStr) + 1
        End If
        
        If p2 > p1 Then
            ExtraerValorJSON = Mid(jsonStr, p1, p2 - p1)
        End If
    End If
End Function
