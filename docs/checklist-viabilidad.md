# Checklist de Viabilidad — ProLech

| Criterio | Tipo | ¿Cumple? | Justificación del equipo |
| :--- | :--- | :--- | :--- |
| Entidad principal clara para el CRUD | Obligatorio | Sí | Nos centraremos en la entidad *Productor Lácteo*. Desde la app se podrá registrar un nuevo ganadero, ver su historial de entregas, actualizar sus datos o darlo de baja. |
| Requiere usuarios que inician sesión | Obligatorio | Sí | Tendremos roles diferenciados. El "Operario de Ruta" solo verá la opción de registrar litros, mientras que el "Administrador" podrá ver los reportes totales. |
| Tiene sentido usarla sin conexión | Obligatorio | Sí | Totalmente. Los establos y zonas de ordeño suelen estar en áreas rurales donde la señal 4G es inestable. El operario guardará los datos localmente y la app se sincronizará al detectar WiFi en la planta. |
| Alcance realista (4-6 pantallas, 1-2 entidades secundarias) | Obligatorio | Sí | Pantallas: Login, Directorio de Productores, Formulario de pesaje (litros), y un Dashboard básico. Entidades secundarias: Lote de Acopio y Usuario. |
| Existe un usuario real para validar | Recomendado | Sí | Las pequeñas y medianas empresas acopiadoras de la región son nuestro modelo de prueba. El problema que resolvemos existe hoy en día. |
| Alguna capacidad nativa aporta valor | Recomendado | Sí | El GPS del celular (para guardar las coordenadas exactas de dónde se recogió la leche) y la cámara (para tomar una foto a la boleta física o leer un código QR del productor). |

*Dictamen del equipo:* La idea es 100% viable. Tiene un alcance controlable para las semanas que dura el ciclo, no dependemos de pasarelas de pago complejas y resuelve un problema logístico real.

---

### Ideas que evaluamos y descartamos

Para llegar a esta conclusión, el equipo analizó otras opciones:
1. *Sistema de Punto de Venta (POS) para un restaurante:* Lo descartamos porque, aunque es útil, involucra demasiadas variables (gestión de mesas, división de cuentas, impresión de tickets en tiempo real) lo que hacía que el alcance fuera muy grande para un primer prototipo. Además, un sistema de restaurante no aprovecha el "modo offline" que exige el curso.
2. *App de control de asistencia de alumnos:* La descartamos porque los flujos de uso son demasiado sencillos y no suponían un reto técnico interesante para aprovechar las capacidades del dispositivo móvil.
