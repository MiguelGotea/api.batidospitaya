Attribute VB_Name = "modWSPNotificaciones"
' =========================================================================================
' Modulo: modWSPNotificaciones
' Descripcion: Funciones para enviar notificaciones de WhatsApp desde MS Access
' Autenticacion: Requiere Token X-WSP-Token
' =========================================================================================

Option Compare Database
Option Explicit

' ⚠️ CAMBIAR ESTE TOKEN POR EL DEFINIDO EN api.batidospitaya.com/api/wsp/auth.php
Private Const WSP_TOKEN As String = "c5b155ba8f6877a2eefca0183ab18e37fe9a6accde340cf5c88af724822cbf50"
Private Const API_URL As String = "https://api.batidospitaya.com/api/wsp/notificacion_puntos.php"

''' <summary>
''' Envia una notificacion de uso de puntos a un cliente via WhatsApp
''' </summary>
''' <param name="membresia">El numero de membresia del cliente</param>
''' <param name="puntos">La cantidad de puntos canjeados</param>
''' <param name="sucursal">El nombre de la sucursal donde se realizo el canje</param>
Public Sub enviarnotificacionclienteusopuntos(ByVal membresia As String, ByVal puntos As Integer, ByVal sucursal As String)
    On Error GoTo ErrorHandler

    Dim http As Object
    Dim jsonBody As String
    Dim response As String

    ' Crear objeto HTTP (Late Binding)
    Set http = CreateObject("MSXML2.XMLHTTP")

    ' Construir el cuerpo JSON
    jsonBody = "{" & _
               """membresia"": """ & membresia & """," & _
               """puntos"": " & puntos & "," & _
               """sucursal"": """ & sucursal & """" & _
               "}"

    ' Configurar y enviar peticion
    With http
        .Open "POST", API_URL, False
        .setRequestHeader "Content-Type", "application/json"
        .setRequestHeader "X-WSP-Token", WSP_TOKEN
        .Send jsonBody
        
        response = .responseText
    End With

    ' Analizar respuesta basica
    If InStr(response, """success"":true") > 0 Then
        ' Debug.Print "Notificacion enviada correctamente"
    Else
        ' MsgBox "Error al enviar notificacion WSP: " & response, vbCritical, "Error de Sistema"
    End If

    Set http = Nothing
    Exit Sub

ErrorHandler:
    MsgBox "Error de conexion al enviar notificacion WSP: " & Err.Description, vbCritical, "Error de Red"
    If Not http Is Nothing Then Set http = Nothing
End Sub
