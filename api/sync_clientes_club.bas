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
    sucursal = codigolocal()
    
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
            membresia = ParseSimpleJSONValue(cleanObj, "membresia")
            ' Extraer cedula
            cedula = ParseSimpleJSONValue(cleanObj, "cedula")
            
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

' ========== SUB: SINCRONIZAR DATOS COMPLETOS LOCALES ==========
' Descarga y actualiza desde el host: Cedula, Nombre, Apellidos, Celular, Cumpleanos y Correo
' para todos los clientes de la sucursal actual.
' Solo sobreescribe campos que vengan con valor en el host (no pisa datos locales con nulos).
Public Sub SincronizarDatosLocales()
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
    sucursal = codigolocal()

    If IsNull(sucursal) Or sucursal = "" Then
        MsgBox "No se pudo determinar el código de sucursal local.", vbExclamation, "Error de Sincronización"
        Exit Sub
    End If

    ' 2. Llamar API con el nuevo endpoint de datos completos
    url = API_BASE_URL & "obtener_datos_clientes_sucursal.php?token=" & API_TOKEN & "&sucursal=" & sucursal

    Set http = CreateObject("MSXML2.XMLHTTP.6.0")
    http.Open "GET", url, False
    http.send

    response = http.responseText

    ' 3. Verificar respuesta
    If InStr(response, """success"":true") = 0 Then
        MsgBox "Error en la respuesta del servidor: " & response, vbCritical, "Error de API"
        GoTo Cleanup
    End If

    ' 4. Parsear JSON y actualizar tabla local ClientesClub
    ' Estructura esperada: {"datos":[{"membresia":123,"nombre":"...","apellido":"...","cedula":"...","celular":"...","fecha_nacimiento":"YYYY-MM-DD","correo":"..."},...]}

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
        Dim nombre As String
        Dim apellido As String
        Dim cedula As String
        Dim celular As String
        Dim fechaNacimiento As String
        Dim correo As String

        For Each obj In objetos
            ' Limpiar llaves si existen
            Dim cleanObj As String
            cleanObj = Replace(Replace(obj, "{", ""), "}", "")

            ' Extraer todos los campos
            membresia       = ParseSimpleJSONValue(cleanObj, "membresia")
            nombre          = ParseSimpleJSONValue(cleanObj, "nombre")
            apellido        = ParseSimpleJSONValue(cleanObj, "apellido")
            cedula          = ParseSimpleJSONValue(cleanObj, "cedula")
            celular         = ParseSimpleJSONValue(cleanObj, "celular")
            fechaNacimiento = ParseSimpleJSONValue(cleanObj, "fecha_nacimiento")
            correo          = ParseSimpleJSONValue(cleanObj, "correo")

            ' Solo procesar si tenemos un código de cliente válido
            If membresia <> "" And membresia <> "null" Then
                totalRecibidos = totalRecibidos + 1

                ' Construir cláusula SET solo con los valores que vienen del host
                ' (si el host envía null/vacío no pisamos el dato local)
                Dim setParts As String
                setParts = ""

                If nombre <> "" And nombre <> "null" Then
                    setParts = setParts & "Nombre = '" & Replace(nombre, "'", "''") & "', "
                End If

                If apellido <> "" And apellido <> "null" Then
                    setParts = setParts & "Apellidos = '" & Replace(apellido, "'", "''") & "', "
                End If

                If cedula <> "" And cedula <> "null" Then
                    setParts = setParts & "Cedula = '" & Replace(cedula, "'", "''") & "', "
                End If

                If celular <> "" And celular <> "null" Then
                    setParts = setParts & "Celular = '" & Replace(celular, "'", "''") & "', "
                End If

                ' Fecha: Access SQL usa formato #YYYY-MM-DD#
                If fechaNacimiento <> "" And fechaNacimiento <> "null" Then
                    setParts = setParts & "Cumpleanos = #" & fechaNacimiento & "#, "
                End If

                If correo <> "" And correo <> "null" Then
                    setParts = setParts & "Correo = '" & Replace(correo, "'", "''") & "', "
                End If

                ' Solo ejecutar UPDATE si hay al menos un campo con valor
                If Len(setParts) > 0 Then
                    ' Quitar coma final antes de WHERE
                    setParts = Left(setParts, Len(setParts) - 2)

                    db.Execute "UPDATE ClientesClub SET " & setParts & _
                               " WHERE CodCliente = " & membresia, dbFailOnError

                    If db.RecordsAffected > 0 Then
                        totalActualizados = totalActualizados + 1
                    End If
                End If
            End If
        Next obj
    End If

    MsgBox "Sincronización de datos completada." & vbCrLf & _
           "Sucursal: " & sucursal & vbCrLf & _
           "Registros recibidos: " & totalRecibidos & vbCrLf & _
           "Localmente actualizados: " & totalActualizados, vbInformation, "Éxito"

Cleanup:
    Set http = Nothing
    Set rs = Nothing
    Set db = Nothing
    Exit Sub

ErrorHandler:
    MsgBox "Error durante la sincronización de datos: " & Err.Description, vbCritical, "Error Crítico"
    Resume Cleanup
End Sub

' Función auxiliar para extraer valores de un objeto JSON simple "key":value o "key":"value"
Private Function ParseSimpleJSONValue(jsonStr As String, key As String) As String
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
            ParseSimpleJSONValue = Mid(jsonStr, p1, p2 - p1)
        End If
    End If
End Function
