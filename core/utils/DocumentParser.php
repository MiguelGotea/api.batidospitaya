<?php
/**
 * Utilidad para procesamiento y extracción de contenido de documentos
 * Ubicación: /core/utils/DocumentParser.php
 */

class DocumentParser
{
    /**
     * Extrae el texto plano de un archivo .docx (Word)
     * Utiliza ZipArchive para leer el XML interno sin dependencias externas pesadas.
     * 
     * @param string $filePath Ruta absoluta al archivo
     * @return string Texto extraído
     * @throws Exception Si el archivo no es válido o no se puede abrir
     */
    public static function docxToText($filePath)
    {
        if (!file_exists($filePath)) {
            throw new Exception("El archivo no existe: " . $filePath);
        }

        $zip = new ZipArchive();
        if ($zip->open($filePath) === true) {
            $xmlContent = "";
            
            // El contenido principal de un .docx está en word/document.xml
            $index = $zip->locateName('word/document.xml');
            if ($index !== false) {
                $xmlContent = $zip->getFromIndex($index);
            }
            
            $zip->close();

            if (empty($xmlContent)) {
                return "";
            }

            // Eliminar etiquetas XML para quedarnos con el texto
            // Usamos strip_tags pero con cuidado de preservar espacios entre párrafos
            $xmlContent = str_replace(['</w:p>', '</w:r>', '<w:tab/>'], ["\n", " ", " "], $xmlContent);
            $cleanText = strip_tags($xmlContent);
            
            return trim($cleanText);
        } else {
            throw new Exception("No se pudo abrir el archivo .docx (posible formato inválido o protegido)");
        }
    }

    /**
     * Determina si un archivo es una imagen basándose en su extensión/mimetype
     */
    public static function isImage($mimeType)
    {
        $imageTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/heic'];
        return in_array(strtolower($mimeType), $imageTypes);
    }

    /**
     * Procesa un PDF o Word y extrae su texto o lo prepara para la IA
     * @param array $fileData Arreglo de $_FILES
     */
    public static function parseDocument($fileData)
    {
        $extension = strtolower(pathinfo($fileData['name'], PATHINFO_EXTENSION));
        $mimeType = $fileData['type'];

        if ($extension === 'docx') {
            return [
                'type' => 'text',
                'content' => self::docxToText($fileData['tmp_name'])
            ];
        }

        if ($extension === 'pdf' || self::isImage($mimeType)) {
            return [
                'type' => 'inline_data',
                'mime_type' => $mimeType,
                'data' => base64_encode(file_get_contents($fileData['tmp_name']))
            ];
        }

        throw new Exception("Formato de archivo no soportado.");
    }

    /**
     * Utiliza la IA para 'destilar' un perfil de puesto: convertir un documento desordenado
     * en una lista limpia de requerimientos en Markdown.
     * 
     * @param AIService $aiService Instancia de AIService configurada
     * @param string $rawContent Texto extraído o descripción del documento
     * @param array $extraParts Opcional, si se envía PDF/Imagen directo
     * @return string Markdown destilado
     */
    public static function distillProfile($aiService, $rawContent = '', $extraParts = [])
    {
        $systemPrompt = "Eres un experto en Recursos Humanos y Reclutamiento Técnico.";
        $userPrompt = "Tu tarea es analizar el documento adjunto (Perfil de Puesto) y extraer ÚNICAMENTE la información relevante para un reclutador en formato Markdown limpio.
        
        Debes extraer:
        1. NOMBRE DEL CARGO
        2. REQUISITOS OBLIGATORIOS (Educación, Experiencia mínima, Certificaciones).
        3. COMPETENCIAS TÉCNICAS (Software, Idiomas, Herramientas).
        4. COMPETENCIAS BLANDAS (Liderazgo, Trabajo en equipo, etc).
        5. BENEFICIOS PRINCIPALES (Opcional si están).

        IMPORTANTE: No incluyas intros, despedidas ni texto irrelevante. Solo las listas de puntos. Si la información no está en el documento, no la inventes.";

        if (!empty($rawContent)) {
            $userPrompt .= "\n\nCONTENIDO DEL DOCUMENTO:\n\"\"\"$rawContent\"\"\"";
        }

        return $aiService->procesarPrompt($systemPrompt, $userPrompt, 0.1, $extraParts);
    }
}
