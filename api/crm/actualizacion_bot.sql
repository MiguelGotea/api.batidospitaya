-- 1. Agregar soporte para imágenes (o documentos) a las intenciones
ALTER TABLE `bot_intents`
    ADD COLUMN IF NOT EXISTS `media_url` VARCHAR(255) NULL DEFAULT NULL 
    COMMENT 'URL pública de la imagen o documento a enviar. Ej: https://erp.batidospitaya.com/menu.jpg' 
    AFTER `priority`;

-- 2. Agregar configuración de Horario de Atención y Mensajes por Instancia VPS
ALTER TABLE `wsp_sesion_vps_`
    ADD COLUMN IF NOT EXISTS `hora_inicio` TIME DEFAULT '00:00:00' COMMENT 'Hora de inicio de atención del bot',
    ADD COLUMN IF NOT EXISTS `hora_fin` TIME DEFAULT '23:59:59' COMMENT 'Hora de fin de atención',
    ADD COLUMN IF NOT EXISTS `mensaje_fuera_horario` TEXT DEFAULT '¡Hola! 🌙 En este momento estamos cerrados. Nuestro horario de atención es de 7:00 AM a 8:00 PM. Déjanos tu mensaje y te atenderemos con gusto enseguida regresemos. 🍓' COMMENT 'Mensaje automático cuando escriben fuera de horario',
    ADD COLUMN IF NOT EXISTS `dias_atencion` VARCHAR(50) DEFAULT '1,2,3,4,5,6,7' COMMENT 'Días de la semana. 1=Lunes, 7=Domingo';

-- 3. Limpiar intenciones anteriores para el CRM bot (Opcional, pero recomendado si se reestructurará todo)
DELETE FROM `bot_intents` WHERE `instancia` = 'wsp-crmbot' OR `instancia` IS NULL;

-- 4. Insertar la nueva estructura robusta de Intents (Batidos Pitaya 100% Fruta Pura)
INSERT INTO `bot_intents` (`intent_name`, `keywords`, `response_templates`, `priority`, `is_active`, `instancia`, `media_url`) VALUES
(
    'saludo', 
    'hola,buenos dias,buenas tardes,buenas,hey,q tal,buenas noches,saludos,que tal', 
    '["¡Hola! 👋 Bienvenido a *Batidos Pitaya*.\\nSomos especialistas en batidos 100% a base de fruta pura, sin conservantes.\\n\\n¿En qué te puedo ayudar hoy? 🍓\\n\\n1️⃣ *Ver Menú* (Batidos, Bowls, Waffles)\\n2️⃣ *Sucursales*\\n3️⃣ *Club Pitaya y Promociones*\\n4️⃣ *Delivery*\\n5️⃣ *Hacer un pedido para llevar*\\n6️⃣ *Hablar con un asesor*"]', 
    10, 1, 'wsp-crmbot', NULL
),
(
    'menu_general', 
    '1,menu,productos,que venden,opciones,precios,lista,catalogo,batidos,bowls,waffles,licuados', 
    '["Aquí tienes nuestro menú digital completo. 👇\\n\\nNuestras categorías principales son:\\n🥤 *Batidos Clásicos, Especiales y Premium*\\n🍦 *Smoothie Bowls* (Açaí, Dragón, Fachento, Ometepe)\\n🧇 *Waffles* (Clásico, Especial, Proteína)\\n🥤 *Batidos Verdes y Limonadas*\\n\\n¡Todos hechos a base de fruta pura y fresca! 🍉🍍\\n\\n*(Nota: Puedes ver la imagen adjunta para más detalles y precios)*"]', 
    10, 1, 'wsp-crmbot', 'https://erp.batidospitaya.com/assets/img/menu.jpg'
),
(
    'sucursales', 
    '2,sucursales,ubicacion,donde estan,direccion,locales,tiendas,managua,departamentos,ubicados', 
    '["Contamos con *13 sucursales* en toda Nicaragua con ambientes súper cómodos para ti y tu familia. 📍\\n\\n*En Managua:* Altamira, Villa Fontana, Las Colinas, Las Brisas, Unica, Plaza Natura.\\n*En los Departamentos:* León, Matagalpa, Estelí, Masaya, Granada, Rivas, Ticuantepe.\\n\\n¿A qué sucursal te gustaría visitarnos para darte la dirección exacta? 🚗"]', 
    10, 1, 'wsp-crmbot', NULL
),
(
    'club_promociones', 
    '3,promociones,promo,club,membresia,descuentos,ofertas,puntos,frecuente,2x1,combos', 
    '["💳 *¡Únete al Club Pitaya!* 🎉\\n\\nLa membresía es vitalicia y con ella gozas de promociones *TODOS LOS DÍAS*. Incluye grandes descuentos exclusivos por compras de:\\n🍹 2 Batidos (Clásicos, Especiales, Proteína o Premium)\\n🧇 2 Waffles Especiales\\n🍪 Galletas de Avena\\n\\nAdemás, **¡Acumulas puntos por cada compra!** que luego puedes canjear por cualquier producto gratis. ¿Te gustaría adquirirla en tu próxima visita?"]', 
    10, 1, 'wsp-crmbot', NULL
),
(
    'delivery', 
    '4,delivery,domicilio,envio,llegan hasta,mandar,pedidosya,hugo,uber', 
    '["Actualmente no contamos con delivery propio, ¡Pero estamos en *PedidosYa*! 🛵\\n\\nTanto en todos los departamentos como en la capital. Búscanos como \'Batidos Pitaya\' desde tu app y te llevaremos fruta fresca directo a tu puerta. 🍓🚲"]', 
    10, 1, 'wsp-crmbot', NULL
),
(
    'pedidos_para_llevar', 
    '5,pedir,pedido,para llevar,ordenar,ordeno,pasar retirando,pasando llevando,quiero pedir,un batido', 
    '["¡Claro que sí! 📝\\n\\nPuedes dejar tu pedido directamente por escrito en este chat e indicarnos a qué sucursal pasarás a retirarlo.\\n\\nEn breve un asistente en tienda revisará los mensajes, lo pasará al mostrador y te confirmará para que vengas a retirar tu orden. 🥤"]', 
    9, 1, 'wsp-crmbot', NULL
),
(
    'ingredientes_alternativas', 
    'azucar,leche,almendra,deslactosada,sin azucar,alergia,fitness,light,vegano,dietetico', 
    '["Todos nuestros productos llevan únicamente fruta 100% pura y fresca. 🍉✨\\n\\nPara endulzar usamos azúcar morena por defecto, pero puedes pedir tus batidos *completamente sin azúcar* si así lo deseas.\\n\\n¡También contamos con deliciosas alternativas de **Leche de Almendra** 🌰 y **Deslactosada**! 🥛🌱 Solo solicítalo al realizar tu pedido en tienda."]', 
    8, 1, 'wsp-crmbot', NULL
),
(
    'humano', 
    '6,asesor,humano,persona,reclamo,queja,problema,mal servicio,ayuda,hablar con alguien', 
    '["Entendido. 🧑‍💻 En un momento dejaremos de dar respuestas automáticas y un representante de nuestro equipo te atenderá de forma personalizada. Por favor, danos los detalles de tu consulta..."]', 
    15, 1, 'wsp-crmbot', NULL
);
