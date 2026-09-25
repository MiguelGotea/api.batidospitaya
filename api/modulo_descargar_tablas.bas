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

' Función auxiliar para codificar URL
Public Function URLEncode(ByVal texto As String) As String
    Dim I As Integer
    Dim char As String
    Dim resultado As String
    
    For I = 1 To Len(texto)
        char = Mid(texto, I, 1)
        Select Case char
            Case "A" To "Z", "a" To "z", "0" To "9", "-", "_", ".", "~"
                resultado = resultado & char
            Case " "
                resultado = resultado & "+"
            Case Else
                resultado = resultado & "%" & Right("0" & Hex(Asc(char)), 2)
        End Select
    Next I
    
    URLEncode = resultado
End Function

Public Function ParseYCrearTabla(ByVal jsonText As String, ByVal nombreTabla As String) As Boolean
    On Error GoTo ErrorHandler
    
    Dim db As DAO.Database
    Dim rs As DAO.Recordset
    Dim successPos As Long
    
    ' Verificar éxito
    successPos = InStr(1, jsonText, """success"":true", vbTextCompare)
    If successPos = 0 Then
        MsgBox "Error en la respuesta del servidor", vbCritical
        ParseYCrearTabla = False
        Exit Function
    End If
    
    ' Extraer estructura
    Dim estructuraStart As Long, estructuraEnd As Long, estructuraText As String
    estructuraStart = InStr(1, jsonText, """estructura"":[", vbTextCompare) + 14
    estructuraEnd = InStr(estructuraStart, jsonText, "],", vbTextCompare)
    estructuraText = Mid(jsonText, estructuraStart, estructuraEnd - estructuraStart)
    
    ' Extraer datos
    Dim datosStart As Long, datosEnd As Long, datosText As String
    datosStart = InStr(1, jsonText, """datos"":[", vbTextCompare) + 9
    datosEnd = InStrRev(jsonText, "]}", , vbTextCompare) - 1
    datosText = Mid(jsonText, datosStart, datosEnd - datosStart + 1)
    
    ' Eliminar tabla si existe y crear nueva
    On Error Resume Next
    CurrentDb.Execute "DROP TABLE " & nombreTabla
    On Error GoTo ErrorHandler
    
    Call CrearTablaDesdeEstructura(nombreTabla, estructuraText)
    
    ' Insertar datos
    Set db = CurrentDb
    Set rs = db.OpenRecordset(nombreTabla, dbOpenDynaset)
    
    If Len(Trim(datosText)) > 2 Then
        Call InsertarDatos(rs, datosText, estructuraText)
    End If
    
    rs.Close
    Set rs = Nothing
    Set db = Nothing
    
    ParseYCrearTabla = True
    Exit Function
    
ErrorHandler:
    MsgBox "Error al parsear: " & Err.Description, vbCritical
    ParseYCrearTabla = False
End Function

Public Sub CrearTablaDesdeEstructura(ByVal nombreTabla As String, ByVal estructuraText As String)
    Dim sql As String
    Dim POS As Long
    Dim regStart As Long, regEnd As Long
    Dim nombreCol As String, tipoAccess As String
    
    sql = "CREATE TABLE " & nombreTabla & " ("
    POS = 1
    
    Do While POS > 0 And POS < Len(estructuraText)
        regStart = InStr(POS, estructuraText, "{", vbTextCompare)
        If regStart = 0 Then Exit Do
        
        regEnd = InStr(regStart, estructuraText, "}", vbTextCompare)
        If regEnd = 0 Then Exit Do
        
        Dim regText As String
        regText = Mid(estructuraText, regStart, regEnd - regStart + 1)
        
        nombreCol = ExtraerValorJSON(regText, "nombre")
        tipoAccess = ExtraerValorJSON(regText, "tipo_access")
        
        If sql <> "CREATE TABLE " & nombreTabla & " (" Then
            sql = sql & ", "
        End If
        
        sql = sql & nombreCol & " " & tipoAccess
        
        POS = regEnd + 1
    Loop
    
    sql = sql & ")"
    CurrentDb.Execute sql
End Sub

Public Sub InsertarDatos(ByRef rs As DAO.Recordset, ByVal datosText As String, ByVal estructuraText As String)
    Dim POS As Long
    Dim regStart As Long, regEnd As Long
    Dim totalReg As Long
    
    POS = 1
    totalReg = 0
    
    Do While POS > 0 And POS < Len(datosText)
        regStart = InStr(POS, datosText, "{", vbTextCompare)
        If regStart = 0 Then Exit Do
        
        regEnd = InStr(regStart, datosText, "}", vbTextCompare)
        If regEnd = 0 Then Exit Do
        
        Dim regText As String
        regText = Mid(datosText, regStart, regEnd - regStart + 1)
        
        rs.AddNew
        Call LlenarCampos(rs, regText, estructuraText)
        rs.Update
        
        totalReg = totalReg + 1
        POS = regEnd + 1
    Loop
    
End Sub

Public Sub LlenarCampos(ByRef rs As DAO.Recordset, ByVal regText As String, ByVal estructuraText As String)
    Dim posEst As Long
    Dim estStart As Long, estEnd As Long
    
    posEst = 1
    
    Do While posEst > 0 And posEst < Len(estructuraText)
        estStart = InStr(posEst, estructuraText, "{", vbTextCompare)
        If estStart = 0 Then Exit Do
        
        estEnd = InStr(estStart, estructuraText, "}", vbTextCompare)
        If estEnd = 0 Then Exit Do
        
        Dim estText As String
        estText = Mid(estructuraText, estStart, estEnd - estStart + 1)
        
        Dim nombreCol As String, tipoAccess As String, Valor As String
        nombreCol = ExtraerValorJSON(estText, "nombre")
        tipoAccess = ExtraerValorJSON(estText, "tipo_access")
        Valor = ExtraerValorJSON(regText, nombreCol)
        
        On Error Resume Next
        If Valor <> "null" And Len(Trim(Valor)) > 0 Then
            Select Case tipoAccess
                Case "INTEGER"
                    rs(nombreCol) = CLng(Valor)
                Case "DOUBLE"
                    rs(nombreCol) = CDbl(Valor)
                Case "DATETIME"
                    If InStr(Valor, " ") > 0 Then
                        rs(nombreCol) = CDate(Valor)
                    ElseIf InStr(Valor, ":") > 0 Then
                        rs(nombreCol) = TimeValue(Valor)
                    Else
                        rs(nombreCol) = CDate(Valor)
                    End If
                Case "YESNO"
                    ' Manejar bit(1) que viene como "0" o "1"
                    If Valor = "1" Or Valor = "true" Or Valor = "True" Then
                        rs(nombreCol) = True
                    Else
                        rs(nombreCol) = False
                    End If
                Case Else
                    rs(nombreCol) = Valor
            End Select
        End If
        On Error GoTo 0
        
        posEst = estEnd + 1
    Loop
End Sub


