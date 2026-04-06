# Estándares de Integración con IA (Gemini)

Este documento define las mejores prácticas para integrar modelos de lenguaje (LLM), específicamente Google Gemini, en el ecosistema de Batidos Pitaya para garantizar baja latencia y alta fiabilidad.

## 1. Configuración del Modelo

Para tareas de clasificación de intenciones y extracción de entidades, el modelo estándar es **Gemini 1.5 Flash**.

- **Model Alias**: `gemini-flash-latest`
- **Temperature**: `0.1` (para respuestas determinísticas)
- **Max Output Tokens**: `1024`

## 2. Configuración de Generación (JSON)

Para asegurar que la IA responda siempre en formato JSON procesable, se debe configurar el `response_mime_type`.

```javascript
generationConfig: {
    temperature: 0.1,
    maxOutputTokens: 1024,
    response_mime_type: 'application/json'
}
```

## 3. Ajustes de Seguridad (Safety Settings)

En el contexto empresarial/administrativo, los filtros de seguridad de Gemini pueden dar falsos positivos (bloqueando frases inofensivas sobre tareas o personal). **Es obligatorio desactivar los bloqueos** para permitir el flujo de trabajo sin interrupciones.

```javascript
safetySettings: [
    { category: 'HARM_CATEGORY_HARASSMENT',         threshold: 'BLOCK_NONE' },
    { category: 'HARM_CATEGORY_HATE_SPEECH',         threshold: 'BLOCK_NONE' },
    { category: 'HARM_CATEGORY_SEXUALLY_EXPLICIT',   threshold: 'BLOCK_NONE' },
    { category: 'HARM_CATEGORY_DANGEROUS_CONTENT',  threshold: 'BLOCK_NONE' }
]
```

## 4. Clasificación en Cascada (Optimización de Latencia)

Para minimizar el tiempo de respuesta al usuario, se recomienda una arquitectura de 3 capas:

1.  **Capa 1 (Local Regex)**: Clasificadores basados en expresiones regulares para frases comunes (0ms).
2.  **Capa 2 (Direct Cloud API)**: Llamada directa a Gemini desde el VPS para evitar saltos de red innecesarios entre servidores (Hostinger -> VPS -> Gemini).
3.  **Capa 3 (Fallback)**: Llamada a un endpoint PHP centralizado (`clasificar.php`) si las capas anteriores fallan.

## 5. Extracción Robusta de JSON

NUNCA confíes en que la IA devolverá exclusivamente el JSON. Siempre implementa una función de extracción que busque los límites de las llaves `{ }`.

### Ejemplo en JavaScript / Node.js
```javascript
function extraerJSON(textoRaw) {
    const inicio = textoRaw.indexOf('{');
    const fin    = textoRaw.lastIndexOf('}');
    
    if (inicio === -1 || fin === -1) {
        throw new Error('No se encontró JSON en respuesta');
    }

    const jsonStr = textoRaw.slice(inicio, fin + 1);
    return JSON.parse(jsonStr);
}
```

### Ejemplo en PHP
```php
function extraerJSON(string $texto): array {
    $inicio = strpos($texto, '{');
    $fin    = strrpos($texto, '}');
    if ($inicio === false || $fin === false) {
        throw new RuntimeException('No se encontró JSON');
    }
    return json_decode(substr($texto, $inicio, $fin - $inicio + 1), true);
}
```

## 6. Manejo de Errores y Logging

Cuando una llamada a la IA falla, es vital loguear el **raw response** de la API. Muchas veces el error de "JSON no encontrado" se debe a que la IA devolvió un mensaje de error o una explicación de bloqueo de seguridad.

```javascript
if (!textoRaw) {
    const reason = resp.data?.candidates?.[0]?.finishReason || 'unknown';
    console.error(`Gemini Error: ${reason}`);
}
```
