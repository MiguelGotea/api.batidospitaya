# Guía de Configuración del Lector de Huella (PC Windows)

Esta guía explica cómo configurar tu computadora Windows para poder probar y usar el lector de huella **Digital Persona U.are.U 4000B** directamente desde el navegador web (Chrome, Edge u Opera) utilizando la API nativa de **WebUSB**.

---

## 1. ¿Por qué se necesita un Driver especial en PC?

- **En Android (Móviles / Tablets):** El sistema operativo permite que el navegador Chrome acceda directamente al puerto USB mediante OTG sin necesidad de instalar controladores adicionales.
- **En Windows:** Por defecto, Windows asocia el lector de huella con el driver propietario de DigitalPersona (o con el servicio biométrico de Windows Hello). WebUSB **no puede comunicarse** con dispositivos que estén siendo controlados por un driver exclusivo. 
  Para solucionarlo, debemos indicarle a Windows que use un controlador genérico llamado **WinUSB**.

---

## 2. Instalación del Driver WinUSB con Zadig (Solo se hace 1 vez por PC)

Para cambiar el controlador del dispositivo usaremos la herramienta gratuita y segura **Zadig**.

### Paso a paso:

1. **Descarga Zadig:**
   - Ve a la página oficial: [https://zadig.akeo.ie/](https://zadig.akeo.ie/)
   - Descarga la versión más reciente (ej. `Zadig 2.8` o superior). Es un archivo ejecutable portátil (no requiere instalación).

2. **Conecta el lector de huella:**
   - Asegúrate de conectar el lector USB **Digital Persona** en un puerto USB de la computadora.

3. **Ejecuta Zadig:**
   - Abre el archivo descargado `zadig.exe` con privilegios de Administrador.

4. **Mostrar todos los dispositivos:**
   - En el menú superior de Zadig, ve a **Options** y activa la opción **List All Devices** (Listar todos los dispositivos).

5. **Selecciona el lector:**
   - En la lista desplegable central, busca y selecciona **U.are.U® 4000B Fingerprint Reader** o **DigitalPersona U.are.U 4000B**.
   - *Nota:* Asegúrate de comprobar que el **USB ID** sea:
     - **USB ID:** `045E` (Vendor ID) y `00BD` (Product ID).

6. **Cambiar el Driver a WinUSB:**
   - Verás dos cuadros de texto sobre los drivers:
     - A la izquierda (**Driver actual**): Puede decir `DPFPUSB` o estar vacío.
     - A la derecha (**Driver a instalar**): Asegúrate de seleccionar **WinUSB** (usando las flechitas arriba/abajo).
   - Haz clic en el botón grande **Replace Driver** (o *Reinstall Driver* / *Install Driver*).

7. **Espera a que finalice:**
   - El proceso tarda entre 10 y 30 segundos. Una vez finalizado, verás un mensaje confirmando que el driver se instaló con éxito.
   - ¡Listo! Ya puedes recargar la página del ERP y hacer clic en **Conectar Lector**.

---

## 3. ¿Cómo revertir el cambio si es necesario?

Si en algún momento deseas usar el lector con el software de escritorio original de DigitalPersona en lugar de la web:

1. Abre el **Administrador de dispositivos** en Windows (presiona `Windows + X` y elige *Administrador de dispositivos*).
2. Busca la sección **Universal Serial Bus devices** (o *Dispositivos de USB*).
3. Haz clic derecho sobre **U.are.U® 4000B Fingerprint Reader** y selecciona **Desinstalar el dispositivo**.
4. Activa la casilla que dice **"Intentar quitar el controlador de este dispositivo"** (o *Eliminar el software de controlador de este dispositivo*) y haz clic en **Desinstalar**.
5. Desconecta y vuelve a conectar el lector USB. Windows reinstalará automáticamente el driver original.
