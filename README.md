# ProLech: Sistema de Trazabilidad y Gestión Láctea

## 1. Datos generales

| Dato             | Información                                         |
| ---------------- | --------------------------------------------------- |
| Proyecto         | ProLech - Sistema de Gestión para el Acopio de Leche|
| Semana           | Semana 1 — Unidad I                                 |
| Modalidad        | Grupal                                              |
| Área             | Ingeniería de Sistemas                              |
| Tipo de proyecto | Plataforma Web de Gestión Empresarial               |
| Repositorio      | ProLech                                             |

---

## 2. Descripción del proyecto

**ProLech** nace para modernizar el vínculo entre las plantas lecheras y los ganaderos locales. Es una plataforma digital diseñada para rastrear cada gota de leche desde que sale del establo hasta que llega a la planta procesadora. 

Más allá de ser un simple registro, el sistema funciona como un puente de confianza: digitaliza los volúmenes recolectados en las rutas, estandariza las pruebas de laboratorio (calidad) y automatiza las liquidaciones económicas para que los pagos sean justos, rápidos y transparentes para todos.

---

## 3. Contexto del problema

En el sector ganadero tradicional, la relación entre el productor y la empresa acopiadora se basa muchas veces en apuntes a mano, cuadernos de campo y hojas de cálculo desactualizadas. 

Esta informalidad genera un ambiente de desconfianza. El productor no siempre tiene claro por qué se le descontó dinero (por temas de calidad o acidez de la leche), y la empresa pierde horas valiosas de trabajo administrativo intentando cuadrar la leche que entró a la planta con el dinero que debe salir para los pagos.

---

## 4. El problema a resolver

**Falta de trazabilidad y lentitud administrativa.** La gestión manual provoca que la información viaje más lento que la propia leche. Cuando hay un problema de calidad (como leche en mal estado), es difícil identificar rápidamente de qué productor provino, lo que arriesga lotes enteros de producción. Además, el cierre de pagos a fin de mes se vuelve un proceso tedioso y propenso a errores matemáticos.

---

## 5. Nuestra Solución

Desarrollar **ProLech**, un sistema web centralizado que elimine el papel del proceso de acopio. 

Nuestra solución permitirá que el personal en campo o en planta registre los ingresos en tiempo real, que el laboratorio valide la calidad al instante, y que el área de finanzas tenga las planillas de pago generadas automáticamente con un par de clics. Todo bajo un entorno seguro y fácil de usar.

---

## 6. Objetivo general

Construir e implementar una plataforma web integral que digitalice, transparente y optimice la cadena de suministro de acopio lechero, mejorando la relación comercial entre la planta y los productores.

---

## 7. Objetivos específicos

* **Cero papel:** Migrar el 100% de los registros físicos de los ganaderos a una base de datos segura.
* **Control de rutas:** Monitorear el volumen exacto de leche recolectada por cada ruta o zona.
* **Semáforo de calidad:** Implementar un registro rápido de pruebas de laboratorio que bloquee entregas que no cumplan los estándares.
* **Automatización financiera:** Reducir el tiempo de cálculo de pagos semanales o mensuales mediante liquidaciones automáticas.
* **Transparencia:** Generar un historial claro para que cada productor sepa exactamente cuánto entregó y cuánto se le pagó.

---

## 8. Alcance del sistema (Módulos)

### 👥 Directorio de Ganaderos
* Perfil completo del productor (Datos personales, DNI, ubicación de su establo).
* Asignación de rutas de recolección.
* Historial de actividad y estado (Activo/Inactivo).

### 🥛 Recepción y Rutas
* Registro de ingreso diario (Litros, turno mañana/tarde, temperatura inicial).
* Identificación del operario que recibe la carga.

### 🧪 Laboratorio y Calidad
* Formulario de evaluación (Densidad, acidez, porcentaje de grasa).
* Sistema de aprobación o rechazo con campo de observaciones obligatorias.

### 💰 Facturación y Liquidaciones
* Calculadora de pagos basada en el volumen aceptado y bonificaciones/penalizaciones por calidad.
* Generación de recibos o boletas de pago.
* Control de deudas o adelantos a productores.

### 📊 Dashboard Gerencial
* Gráficos estadísticos del volumen acopiado por mes.
* Top de productores con mayor rendimiento.
* Reportes exportables a Excel/PDF.

---

## 9. Perfiles de Usuario

1. **Super Administrador:** Acceso total a configuraciones y reportes financieros.
2. **Operario de Balanza/Acopio:** Su única función es registrar quién entrega la leche y cuántos litros son. Interfaz rápida y directa.
3. **Técnico de Calidad:** Ingresa los resultados de las pruebas químicas.
4. **Productor (Vista de Consulta):** (Fase futura) Acceso básico para que el ganadero vea sus propias entregas y pagos desde su celular.

---

## 10. Flujo de Operación

```text
[ Establo del Productor ]
          │
          ▼
1. Recepción en Planta o Ruta (Registro de Litros)
          │
          ▼
2. Muestra a Laboratorio (Análisis de Calidad)
          ├──► [Rechazado] ──► Notificación al productor
          │
          ▼
3. [Aprobado] Ingreso a Tanque Frío
          │
          ▼
4. Consolidación Quincenal/Mensual
          │
          ▼
5. Cálculo Automático y Generación de Pagos
