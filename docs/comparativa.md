# Análisis de Frameworks Multiplataforma

*Proyecto:* ProLech
*Equipo:* ProLech Team

## Cuadro Comparativo

| Criterio a evaluar | Kotlin Multiplatform (KMP) | Flutter | React Native |
| :--- | :--- | :--- | :--- |
| Curva de aprendizaje | Utiliza Kotlin. Ideal para quienes vienen de Android nativo, aunque requiere adaptarse a los módulos compartidos (JetBrains, 2025). | Utiliza Dart. Exige aprender un lenguaje nuevo y entender el sistema basado en widgets (Google, 2025). | Utiliza JavaScript/TypeScript. Muy amigable si el equipo ya tiene experiencia previa en desarrollo Web (Meta, 2025). |
| Renderizado de UI | Usa Compose Multiplatform. Se puede compartir la lógica pura o también la interfaz gráfica completa (JetBrains, 2025). | Usa el motor Skia/Impeller. Dibuja su propia interfaz desde cero, garantizando que se vea exactamente igual en cualquier pantalla (Google, 2025). | Usa componentes nativos. El código JS invoca a los elementos de interfaz reales de Android o iOS (Meta, 2025). |
| Integración con el sistema | Excelente. El código se compila a binarios nativos, lo que da acceso total a funciones como el GPS o Bluetooth sin intermediarios (JetBrains, 2025). | Buena, pero requiere instalar paquetes externos (plugins) para interactuar con el hardware del teléfono (Google, 2025). | Funcional a través de puentes (bridges), lo que a veces puede generar cuellos de botella en el rendimiento (Meta, 2025). |
| Adopción en la industria | Impulsado por JetBrains y Google. Usado en apps críticas como Netflix y VMWare (JetBrains, 2025). | Gran popularidad en startups. Respaldado por Google y usado por Nubank y Alibaba (Google, 2025). | Consolidado en el mercado. Creado por Meta y usado en Instagram y Shopify (Meta, 2025). |
| Entorno de desarrollo | Nativo en Android Studio. Se puede programar para Android desde Windows (iOS requiere una Mac) (JetBrains, 2025). | Muy versátil. Permite desarrollar para Android o Web desde cualquier sistema operativo (Google, 2025). | Flexible, pero depende en gran medida del entorno de Node.js y herramientas de terceros (Meta, 2025). |

## Decisión del Equipo

Luego de evaluar las tres alternativas, *el equipo ha decidido utilizar Kotlin Multiplatform (KMP) con Compose Multiplatform* para el desarrollo de ProLech.

*Nuestras razones:*
1. Dado que nuestro sistema de acopio requerirá funcionar sin internet (offline-first) y guardar datos locales del GPS en zonas rurales, KMP nos brinda la mejor interacción directa con el hardware nativo del dispositivo sin depender de librerías de terceros que puedan fallar.
2. Nos permite unificar el lenguaje de programación (Kotlin) tanto para la lógica de negocio como para el diseño de pantallas con Compose, optimizando el tiempo de nuestro equipo de desarrollo.

## Bibliografía consultada

* American Psychological Association. (2020). Publication manual of the American Psychological Association (7.ª ed.). https://doi.org/10.1037/0000165-000
* Google. (2025). Flutter documentation. https://docs.flutter.dev/
* JetBrains. (2025). Kotlin Multiplatform documentation. https://kotlinlang.org/docs/multiplatform.html
* Meta. (2025). React Native documentation. https://reactnative.dev/docs/getting-started
