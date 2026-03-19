# Laravel AI SDK en este proyecto

- La configuración vive en `config/ai.php`; las claves API solo en `.env` (ver `.env.example`).
- Los agentes van en `app/Ai/Agents/`; invocar con `Promptable` según la documentación de Laravel 13 AI SDK.
- En **tests**, usar siempre `NombreDelAgente::fake()` para no llamar a proveedores externos; aserciones con `assertPrompted` cuando aplique.
- Rutas que llamen al modelo deben ir **autenticadas**, con validación (Form Request) y **rate limiting** dedicado (`throttle:ai`) para controlar coste y abuso.
- No enviar a terceros datos personales innecesarios; acotar el contexto del prompt al mínimo imprescindible para la tarea.
