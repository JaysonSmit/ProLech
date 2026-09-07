# ProLech — Sistema de Gestión para el Acopio de Leche

Aplicación multiplataforma para gestionar y auditar el proceso de acopio de leche, desde la recolección en campo con los productores hasta su recepción y evaluación en planta.

## Problema que resuelve

Las empresas dedicadas al acopio de leche trabajan con diversos productores de la zona de Juliaca y la región, realizando actividades logísticas de recolección, traslado y recepción. Gran parte de esta información se registra manualmente o se encuentra dispersa, lo que dificulta el control riguroso de los productores, las cantidades exactas recolectadas, las rutas y las incidencias durante el proceso.

ProLech busca centralizar esta información en una plataforma sólida, facilitando el seguimiento transaccional de la leche recolectada en campo hasta su llegada y recepción en la planta, garantizando a la administración datos exactos para la toma de decisiones.

## Público objetivo

La aplicación está dirigida a empresas o centros de acopio de leche que trabajan de forma directa con los productores. Los principales usuarios serán:

* Personal encargado de la recolección en ruta.
* Personal encargado de la recepción en planta.
* Administradores y gerencia del centro de acopio.
* Responsables del control de calidad.

La aplicación podrá utilizarse principalmente durante las actividades de recolección en campo (mediante dispositivos móviles) y la recepción de la leche en planta.

## Funcionalidades previstas

* *F1:* Gestionar y consultar el registro de productores asociados.
* *F2:* Registrar y consultar las rutas de recolección.
* *F3:* Registrar las cantidades de leche recolectadas por cada productor en campo.
* *F4:* Registrar la recepción oficial de los lotes de leche en la planta.
* *F5:* Comparar sistemáticamente la cantidad recolectada en ruta con la cantidad real recibida en planta.
* *F6:* Registrar incidencias ocurridas durante el traslado o la recolección.
* *F7:* Registrar los parámetros e información básica del control de calidad.
* *F8:* Autenticación e inicio de sesión seguro para los usuarios.
* *F9:* Permitir trabajar sin conexión a Internet en zonas de poca cobertura y sincronizar la información al recuperar la conexión.
* *F10:* Consultar el historial detallado de entregas y recolecciones por productor.
* *F11:* Mostrar información gerencial básica sobre los volúmenes recolectados y recibidos.

## Entidad principal del CRUD

*Productor*
La entidad principal del CRUD será Productor, debido a que representa a las personas que entregan la leche al centro de acopio. Esta entidad es el núcleo relacional indispensable para las transacciones de entrega, rutas y pagos.

*Atributos tentativos:*
* idProductor
* nombres
* apellidos
* dni
* telefono
* direccion
* comunidad
* estado

La entidad Productor estará relacionada posteriormente con las entregas y los registros de recolección realizados durante el flujo de trabajo.

## Capacidad nativa prevista

*Ubicación (Geolocalización)*
La aplicación utilizará la ubicación del dispositivo para registrar o consultar las coordenadas exactas de los puntos de recolección y apoyar el seguimiento logístico de las rutas.

Esta capacidad permitirá relacionar una recolección con su ubicación geográfica en tiempo real, facilitando el control de las actividades realizadas por el personal en campo.

## Equipo ProLech

| Integrante | Rol semana 1 |
| :--- | :--- |
| JAYSON SMIT COAQUIRA RAMIREZ | Coordinación |
| YHON FREDY QUILLA LARICO | QA y documentación |
| JHON SAUL MAMANI CRUZ | Lógica y datos |
| ALEX BRAYAN GUTIERREZ HUANCA | UI |

## Tecnologías

* Kotlin Multiplatform
* Compose Multiplatform
* Kotlin
* Android
* Desktop
* Git
* GitHub

La aplicación será desarrollada utilizando Kotlin Multiplatform y Compose Multiplatform, de acuerdo con las tecnologías establecidas para el alcance de este proyecto.

## Targets

* Android
* Desktop

> *Nota:* iOS preparado: requiere macOS para compilar.
