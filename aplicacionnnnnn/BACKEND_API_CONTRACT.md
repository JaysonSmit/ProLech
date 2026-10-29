# ProLech — Contrato de API Laravel para el Frontend Android

> **Base URL (emulador):** `http://10.0.2.2:8000`  
> **Base URL (producción):** `https://tu-dominio.com`  
> **Autenticación:** Laravel Sanctum — Bearer Token en header `Authorization`  
> **Content-Type:** `application/json` en todos los endpoints

---

## 1. Autenticación

### POST `/api/login`
No requiere token. El frontend lo llama con las credenciales del conductor.

**Request:**
```json
{
  "email": "conductor@prolech.com",
  "password": "secreto123"
}
```

**Response 200 OK:**
```json
{
  "token": "1|abc123xyz...",
  "user": {
    "id": 5,
    "nombres": "Carlos",
    "apellidos": "Mendoza Choque",
    "email": "conductor@prolech.com",
    "rol": "CONDUCTOR"
  }
}
```

**Response 401 Unauthorized:**
```json
{ "message": "Credenciales incorrectas" }
```

**Response 422 Unprocessable Entity:**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["El campo email es obligatorio."],
    "password": ["El campo contraseña es obligatorio."]
  }
}
```

**Laravel — código mínimo:**
```php
// routes/api.php
Route::post('/login', [AuthController::class, 'login']);

// AuthController.php
public function login(Request $request)
{
    $request->validate([
        'email'    => 'required|email',
        'password' => 'required|string',
    ]);

    $user = User::where('email', $request->email)->first();

    if (!$user || !Hash::check($request->password, $user->password)) {
        return response()->json(['message' => 'Credenciales incorrectas'], 401);
    }

    $token = $user->createToken('prolech-android')->plainTextToken;

    return response()->json([
        'token' => $token,
        'user'  => [
            'id'        => $user->id,
            'nombres'   => $user->nombres,
            'apellidos' => $user->apellidos,
            'email'     => $user->email,
            'rol'       => $user->rol->nombre, // relación BelongsTo
        ],
    ]);
}
```

---

### POST `/api/logout`
Requiere Bearer Token. Invalida el token actual.

**Response 200 OK:**
```json
{ "message": "Sesión cerrada correctamente" }
```

**Laravel:**
```php
// routes/api.php — dentro del grupo auth:sanctum
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

// AuthController.php
public function logout(Request $request)
{
    $request->user()->currentAccessToken()->delete();
    return response()->json(['message' => 'Sesión cerrada correctamente']);
}
```

---

## 2. Mi Ruta

### GET `/api/mi-ruta`
Requiere Bearer Token. Devuelve la asignación activa de HOY para el conductor autenticado, incluyendo su lista de paradas (ganaderos a visitar).

**Response 200 OK:**
```json
{
  "asignacion_id": 42,
  "ruta_codigo": "A-1",
  "ruta_nombre": "Huata Centro – Costa",
  "sector": "Sector Norte",
  "vehiculo_placa": "EO-492",
  "vehiculo_marca": "Volvo FMX",
  "capacidad_litros": 1200.0,
  "fecha": "2026-09-10",
  "paradas": [
    {
      "id": 101,
      "orden": 1,
      "ganadero_id": 7,
      "ganadero_nombre": "Juan Quispe Condori",
      "ganadero_dni": "40201653",
      "direccion": "Av. Huata Km 3",
      "geo_lat": -15.8234,
      "geo_lon": -70.0123,
      "atendido": false
    },
    {
      "id": 102,
      "orden": 2,
      "ganadero_id": 12,
      "ganadero_nombre": "Martina Choque Flores",
      "ganadero_dni": "29301823",
      "direccion": null,
      "geo_lat": null,
      "geo_lon": null,
      "atendido": true
    }
  ]
}
```

**Response 404 Not Found** (sin asignación hoy):
```json
{ "message": "Sin ruta asignada para hoy" }
```

**Response 401 Unauthorized** (token inválido/expirado):
```json
{ "message": "Unauthenticated." }
```

**Laravel:**
```php
// routes/api.php
Route::get('/mi-ruta', [RutaController::class, 'miRuta'])->middleware('auth:sanctum');

// RutaController.php
public function miRuta(Request $request)
{
    $conductor = $request->user();
    $hoy = now()->toDateString();

    $asignacion = AsignacionRuta::with(['ruta', 'vehiculo', 'paradas.ganadero'])
        ->where('conductor_id', $conductor->id)
        ->whereDate('fecha', $hoy)
        ->whereIn('estado', ['EN_CURSO', 'PENDIENTE'])
        ->first();

    if (!$asignacion) {
        return response()->json(['message' => 'Sin ruta asignada para hoy'], 404);
    }

    return response()->json([
        'asignacion_id'    => $asignacion->id,
        'ruta_codigo'      => $asignacion->ruta->codigo,
        'ruta_nombre'      => $asignacion->ruta->nombre,
        'sector'           => $asignacion->ruta->sector,
        'vehiculo_placa'   => $asignacion->vehiculo->placa,
        'vehiculo_marca'   => $asignacion->vehiculo->marca,
        'capacidad_litros' => $asignacion->vehiculo->capacidad_litros,
        'fecha'            => $asignacion->fecha,
        'paradas'          => $asignacion->paradas->sortBy('orden')->map(fn($p) => [
            'id'             => $p->id,
            'orden'          => $p->orden,
            'ganadero_id'    => $p->ganadero->id,
            'ganadero_nombre'=> $p->ganadero->nombres . ' ' . $p->ganadero->apellidos,
            'ganadero_dni'   => $p->ganadero->dni,
            'direccion'      => $p->ganadero->direccion,
            'geo_lat'        => $p->ganadero->geo_lat,
            'geo_lon'        => $p->ganadero->geo_lon,
            'atendido'       => $p->atendido,
        ])->values(),
    ]);
}
```

---

## 3. Acopios

### POST `/api/acopios`
Requiere Bearer Token. Registra un nuevo acopio de leche con datos del Lactoscan.

**Request:**
```json
{
  "asignacion_id":    42,
  "ganadero_id":      7,
  "litros":           45.5,
  "porcentaje_agua":  1.2,
  "acidez_ph":        6.5,
  "gps_lat":          -15.8234,
  "gps_lon":          -70.0123,
  "litros_tina":      null
}
```

| Campo | Tipo | Requerido | Descripción |
|---|---|---|---|
| `asignacion_id` | integer | ✅ | ID de la asignación activa del conductor |
| `ganadero_id` | integer | ✅ | ID del ganadero en la BD |
| `litros` | decimal | ✅ | Litros medidos (>0) |
| `porcentaje_agua` | decimal | ✅ | % agua Lactoscan (0-100) |
| `acidez_ph` | decimal | ✅ | pH medido (0-14) |
| `gps_lat` | decimal | ❌ | Latitud GPS al momento |
| `gps_lon` | decimal | ❌ | Longitud GPS al momento |
| `litros_tina` | decimal | ❌ | Medición alternativa en tina |

**Response 201 Created:**
```json
{
  "id":            1023,
  "ticket_numero": "TK-02341",
  "estado":        "APROBADO",
  "hora_acopio":   "2026-09-10T06:14:00.000000Z",
  "mensaje":       "Acopio registrado correctamente"
}
```

> **Lógica de estado automático:**  
> - `porcentaje_agua > 5.0` → estado = `RECHAZADO`  
> - `acidez_ph < 6.0 || acidez_ph > 7.0` → estado = `OBSERVADO`  
> - Todos los valores normales → estado = `APROBADO`

**Response 422 Unprocessable Entity:**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "litros":          ["El campo litros debe ser mayor a 0."],
    "ganadero_id":     ["El ganadero no existe o no está en tu ruta."]
  }
}
```

**Laravel:**
```php
// routes/api.php
Route::post('/acopios', [AcopioController::class, 'store'])->middleware('auth:sanctum');

// AcopioController.php
public function store(Request $request)
{
    $validated = $request->validate([
        'asignacion_id'   => 'required|exists:asignaciones_ruta,id',
        'ganadero_id'     => 'required|exists:ganaderos,id',
        'litros'          => 'required|numeric|min:0.1',
        'porcentaje_agua' => 'required|numeric|min:0|max:100',
        'acidez_ph'       => 'required|numeric|min:0|max:14',
        'gps_lat'         => 'nullable|numeric',
        'gps_lon'         => 'nullable|numeric',
        'litros_tina'     => 'nullable|numeric|min:0',
    ]);

    // Calcular estado automáticamente
    $estado = 'APROBADO';
    if ($validated['porcentaje_agua'] > 5.0) {
        $estado = 'RECHAZADO';
    } elseif ($validated['acidez_ph'] < 6.0 || $validated['acidez_ph'] > 7.0) {
        $estado = 'OBSERVADO';
    }

    // Calcular diferencia (merma)
    $diferencia = null;
    if (isset($validated['litros_tina'])) {
        $diferencia = $validated['litros'] - $validated['litros_tina'];
    }

    // Número de ticket secuencial
    $ticket = 'TK-' . str_pad(Acopio::count() + 1, 5, '0', STR_PAD_LEFT);

    $acopio = Acopio::create([
        'asignacion_id'     => $validated['asignacion_id'],
        'ganadero_id'       => $validated['ganadero_id'],
        'conductor_id'      => $request->user()->id,
        'litros'            => $validated['litros'],
        'litros_tina'       => $validated['litros_tina'] ?? null,
        'diferencia_litros' => $diferencia,
        'porcentaje_agua'   => $validated['porcentaje_agua'],
        'acidez_ph'         => $validated['acidez_ph'],
        'estado'            => $estado,
        'sync_estado'       => 'ENVIADO',
        'gps_lat'           => $validated['gps_lat'] ?? null,
        'gps_lon'           => $validated['gps_lon'] ?? null,
        'ticket_numero'     => $ticket,
        'hora_acopio'       => now(),
        'creado_en'         => now(),
    ]);

    // Marcar la parada como atendida
    ParadaRuta::where('asignacion_id', $validated['asignacion_id'])
        ->where('ganadero_id', $validated['ganadero_id'])
        ->update(['atendido' => true, 'hora_atencion' => now()]);

    return response()->json([
        'id'           => $acopio->id,
        'ticket_numero'=> $acopio->ticket_numero,
        'estado'       => $acopio->estado,
        'hora_acopio'  => $acopio->hora_acopio,
        'mensaje'      => 'Acopio registrado correctamente',
    ], 201);
}
```

---

## 4. Configuración de Laravel

### `config/sanctum.php`
```php
// Tiempo de expiración del token (en minutos). null = no expira.
'expiration' => null,  // o 60 * 24 * 7 para 7 días
```

### `app/Http/Kernel.php` — Middleware API
```php
'api' => [
    \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
    'throttle:api',
    \Illuminate\Routing\Middleware\SubstituteBindings::class,
],
```

### `bootstrap/app.php` (Laravel 11)
```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->statefulApi();
})
```

### CORS — `config/cors.php`
```php
'paths'         => ['api/*'],
'allowed_origins' => ['*'],  // En producción: restringe a tu dominio
'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
'allowed_headers' => ['Content-Type', 'Authorization', 'Accept'],
```

### Variables de entorno — `.env`
```
APP_URL=http://10.0.2.2:8000
SANCTUM_STATEFUL_DOMAINS=10.0.2.2:8000
SESSION_DOMAIN=10.0.2.2
```

---

## 5. Modelos Eloquent mínimos necesarios

```php
// app/Models/User.php
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = ['nombres', 'apellidos', 'email', 'password', 'rol_id', 'activo'];
    protected $hidden   = ['password', 'remember_token'];

    public function rol() {
        return $this->belongsTo(Rol::class, 'rol_id');
    }
}

// app/Models/AsignacionRuta.php
class AsignacionRuta extends Model
{
    protected $table = 'asignaciones_ruta';
    protected $fillable = ['fecha', 'conductor_id', 'vehiculo_id', 'ruta_id', 'estado'];

    public function ruta()     { return $this->belongsTo(Ruta::class); }
    public function vehiculo() { return $this->belongsTo(Vehiculo::class); }
    public function paradas()  { return $this->hasMany(ParadaRuta::class, 'asignacion_id'); }
}

// app/Models/ParadaRuta.php
class ParadaRuta extends Model
{
    protected $table = 'paradas_ruta';
    protected $fillable = ['asignacion_id', 'ganadero_id', 'orden', 'atendido', 'hora_atencion'];

    public function ganadero() { return $this->belongsTo(Ganadero::class); }
}

// app/Models/Acopio.php
class Acopio extends Model
{
    protected $table = 'acopios';
    protected $fillable = [
        'asignacion_id', 'ganadero_id', 'conductor_id',
        'litros', 'litros_tina', 'diferencia_litros',
        'porcentaje_agua', 'acidez_ph', 'estado', 'sync_estado',
        'gps_lat', 'gps_lon', 'ticket_numero', 'hora_acopio', 'creado_en',
    ];
}
```

---

## 6. Resumen de endpoints que consume la app

| Método | Endpoint | Auth | Descripción |
|--------|----------|------|-------------|
| `POST` | `/api/login` | ❌ | Autenticar conductor, obtener token Sanctum |
| `POST` | `/api/logout` | ✅ Bearer | Invalidar token, cerrar sesión |
| `GET` | `/api/mi-ruta` | ✅ Bearer | Obtener ruta + paradas del día |
| `POST` | `/api/acopios` | ✅ Bearer | Registrar acopio de leche |

---

## 7. Checklist de implementación Laravel

- [ ] `composer require laravel/sanctum` + `php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"`
- [ ] `php artisan migrate` (crea tabla `personal_access_tokens`)
- [ ] Agregar `HasApiTokens` al modelo `User`
- [ ] Crear controladores: `AuthController`, `RutaController`, `AcopioController`
- [ ] Registrar rutas en `routes/api.php`
- [ ] Configurar CORS en `config/cors.php`
- [ ] Crear un usuario conductor de prueba: `php artisan tinker` → `User::create([...])`
- [ ] Probar con Postman o Insomnia antes de conectar el APK
- [ ] En el emulador, la app llega al PC en `10.0.2.2:8000`; ejecutar `php artisan serve --host=0.0.0.0 --port=8000`
