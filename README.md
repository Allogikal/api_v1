<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

## Требования
Проект запускать только с конфигурацией (модули) OpenServer:

HTTP: Apache_2.4-PHP_8.0-8.1+Nginx_1.21; <br>
PHP: 8.1; <br>
MySQL / MariaDB: MySQL-8.0-Win10.

### Примечание <br>
#### Не забыть настроить `.env` для бд, создать пустую базу данных. Postman-коллекция в корне проекта. В заголовках всех запросов убираем `Accept: /*/` и ставим `Accept: application/json`. В роутах которые требуют аутентификацию используем токен сгенерированный при авторизации пользователя и в разделе `Auth` в Postman ставим `Bearer` и вставляем этот самый сгенерированный токен.

## Экскурсия по разработке API за час.

Создаем пустой проект выполняя команду: <br>
`composer create-project laravel/laravel api_laravel`

Как пример можно рассмотреть роли запросов, сразу настроить проверку на администратора и подключить middleware:`[auth:sanctum]`
Для этого открываем файлик `app/Http/Kernel.php` и раскомментируем строчку `\Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,` в участке кода
```php
   'api' => [
    \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
    'throttle:api',
    \Illuminate\Routing\Middleware\SubstituteBindings::class,
      ]
```
Данными действиями мы подключили пакет Sanctum предназначенный для работы с авторизацией пользователей через токены (по заданию используем так называемые Bearer tokens)...
Что такое токены и вообщем работа с Sanctum прописана в документации Laravel'а).
Что касается создания middleware для проверки на статус администратора прописываем команду: <br>
`php artisan make:middleware AdminMiddleware` <br>
И в handle прописываем следующий код <br>
```php
   public function handle(Request $request, Closure $next)
   {
        // Здесь мы проходим в пакет [Auth] в [vendor] и вытаскиваем оттуда пользователя
        // Который был авторизован и проверяем по полю [admin] является ли он админом или нет
        // Иначе просто показываем сообщение и код не авторизован.
        
        if (Auth::user()->admin) {  
            return $next($request);
        }
        return response([
           'message' => 'You are not admin!'
        ], 403);
   }
```
Так мы настроили проверку на админа и остается просто в файлике `app/Http/Kernel.php` опрокинуть этот middleware <br>
Делаем! Прописываем эту строку <br>
`'admin' => \App\Http\Middleware\AdminMiddleware::class,` <br>
Теперь доступна защита для админских роутов <br>
Идем по порядку, разберемся с миграциями таблиц. <br>
А также поочереди создаем три модели (с миграциями и фабриками) для продуктов и корзин пользователей: <br>
`php artisan make:model Product -mf` <br>
`php artisan make:model Cart -mf` <br>
`php artisan make:model Order -mf` <br>
Переходим в модель `Product` и пишем: <br>
```php
   class Product extends Model
   {
    use HasFactory;
    protected $fillable = [
      'name',
      'description',
      'price',
    ];
   }
```
Переходим в модель `Order` и пишем: <br>
```php
   class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'product_id',
        'price_product',
        'quantity'
    ];
}
```
Прыгаем в модель `Cart` и пишем: <br>
```php
   class Cart extends Model
   {
    use HasFactory;

    protected $fillable = [
        'user_id',
        'product_id',
        // Цена товара
        'price_product',
        // Количество каждой единицы товара
        'quantity'
    ];
   }
```
И проверим существующую модель `User` <br>
```php
   class User extends Authenticatable
{
    // Если нет HasApiTokens, прописываем чтобы получать токены авторизации
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'fio',
        'email',
        'password',
        // Желательно чтобы admin был в данном защищенном поле $fillable
        'admin'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

}
```
--------------------------------- <br>
Фабрики опускаю (автозаполнение впринципе имеется в postman коллекции). Посмотрим миграции. <br>
--------------------------------- <br>

Открываем миграцию пользователей: <br>
```php
   public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('fio');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            // Это то логическое поле по которому мы проверяем является ли пользователь админом или нет
            $table->boolean('admin')->default(false);
            $table->rememberToken();
            $table->timestamps();
        });
    }
```
Открываем миграцию продуктов: <br>
```php
   public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            // У каждого продукта есть и название и описание
            $table->string('name');
            $table->string('description')->default('Empty');
            // Стоимость продукта - Число с плавающей точкой, можно просто офрмить integer, но за копейки иногда готовы убить, так что оставим десятичное число)))
            $table->decimal('price', 5, 2);
            $table->timestamps();
        });
    }
```
Открываем миграцию заказов: <br>
```php
   public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->unsignedBigInteger('product_id');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->integer('price_product')->default(0);
            $table->integer('quantity')->default(1);
            $table->timestamps();
        });
    }
```
Открываем миграцию корзин товаров пользователей: <br>
```php
   public function up()
    {
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->string('user_id');
            // Беззнаковое int число используется для связи с таблицой продуктов (а конкретно с его ID)
            $table->unsignedBigInteger('product_id');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            // Сюда пихаем стоимость добавленного продукта
            $table->integer('price_product')->default(0);
            // Здесь просто количество товара в корзине
            $table->integer('quantity')->default(1);
            $table->timestamps();
        });
    }
```
Модельки с миграциями готовы к работе <br>
Производим миграции в бд. <br>
`php artisan migrate` <br>
[В случае ошибок читать ПРИМЕЧАНИЕ про бд] <br>

Теперь начинаем создавать контроллеры и делаем роутинг. <br>

Контроллер для обработки Аутентификации: <br>
`php artisan make:controller AuthController` <br>

Можно прописать это через общий ресурс также с различными методами, 
но в таком случае не получится раскинуть по группам маршруты
и сделать группы с отдельно публичными методами, методами аутентифицированного пользователя,
и с методами админа<br>

Ресурсный контроллер для обработки Продуктов: <br>
`php artisan make:controller ProductController --resource` <br>

И ресурсный контроллер для обработки Корзины: <br>
`php artisan make:controller CartController --resource` <br>

И ресурсный контроллер для обработки Заказов: <br>
`php artisan make:controller OrderController --resource` <br>

Заходим в `AuthController` и пишем три метода <br>
[ <br>
  * Метод register() для регистрации пользователей в бд <br>
  * Метод login() для авторизации пользователей <br>
  * Метод logout() для  пользователей <br>
] <br>

```php
   public function register(Request $request) {
        // Берем поля и валидируем на обязательность заполнения полей и уникальность пользователей по их email
        // В поле пароля стоит маска confirmed - означающая что при отправке запроса необходимо прописать password_confirmation
        // По сути поле подтверждения пароля при регисрации
        // Если дополнительного поля в запросе не будет - выдаст соответсвующее сообщение - пароль не подтвержден!
        $fields = $request->validate([
            'fio' => 'required|string',
            'email' => 'required|string|unique:users,email',
            'password' => 'required|string|confirmed'
        ]);
        // Здесь создаем пользователя и хешируем пароль
        $user = User::create([
            'fio' => $fields['fio'],
            'email' => $fields['email'],
            'password' => bcrypt($fields['password']),
        ]);
        // Данным образом создается токен и вытаскивается из модели пользователя
        // которая включает в себя класс Authenticatable в котором и присутствуют данные методы
        // Забираем сам токен
        $token = $user->createToken('token')->plainTextToken;
        // Ответом присылаем созданного пользователя и токен
        // Статус код задаем Created, все статус коды доступны в инете
        $response = [
            'user' => $user,
            'token' => $token
        ];
        return response($response, 201);
    }

    public function login(Request $request) {
        // Тот же принцип валидации
        $fields = $request->validate([
            'email' => 'required|string',
            'password' => 'required|string'
        ]);
        // Ищем пользователя по email и вытаскиваем его (метод first() необходим
        // чтобы вытащить первый попавшийся email, но повторяющихся email у нас и
        // не будет существовать ввиду присутствия валидации при регистрации
        $user = User::where('email', $fields['email'])->first();
        // Разхешируем пароль с помощью класса Hash и проверяем на соответствие с паролем
        // найденного пользователя
        // А если пользователь не найден или не верно введен пароль то ответ
        // неутешителен - Статус Unauthorized и сообщение неверные данные
        if (!$user || !Hash::check($fields['password'], $user->password)) {
            return response([
                'message' => 'Bad credentinals'
            ], 401);
        }
        // Также возвращаем токен в случае если данные верны
        $token = $user->createToken('token')->plainTextToken;
        // Тот же ответ что и при регистрации
        $response = [
            'user' => $user,
            'token' => $token
        ];
        return response($response, 201);
    }

    public function logout() {
    // Лезем в класс авторизации и через метод auth находим пользователя (авторизованного) и удаляем все токены связанные с ним
    // Появляется вопрос где храняться токены
    // В миграциях имеется [personal_access_tokens_table]
        auth()->user()->tokens()->delete();
        return [
            'message' => 'Logged out',
        ];
        // Раз токены пользователя удалены, то соответственно
        // и гость не сможет посетить защищенные роуты
    }
```
Теперь поработаем в ресурсном контроллере `ProductController`<br>

```php
   class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function index()
    {
        // Вытаскиваем через модели все существующие продукты
        return Product::all();
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Валидируем поля на обязательность заполнения
        // И создаем данный продукт 
        $request->validate([
            'name' => 'required',
            'price' => 'required'
        ]);
        return Product::create($request->all());
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        // Можем посмотреть на конкретный продукт
        // Но в задании он не нужен впринципе)
        return Product::find($id);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
       // Находим через модель продукта по id
       // и обновляем данные продукта
        $product = Product::find($id);
        $product->update($request->all());
        return response([
            'updated' => $product
        ], 201);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        // Такой записью просто удаляем продукт по id, при этом
        // Отображая данный удаленный продукт 
        return response([
            'deleted' => Product::find($id),
            'status' => Product::destroy($id)
        ]);
    }

}
```

Поработаем в ресурсном контроллере `CartController`<br>

```php
   class CartController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Вытаскиваем id авторизованного пользователя
        // Находим корзину конкретного авторизованного пользователя
        // Подсчитываем коилчество товаров в корзине и стоимость соответственно
        $user = \auth()->id();
        $cart = Cart::where(['user_id' => $user])->get();
        $sum_quantities = Cart::where(['user_id' => $user])->sum('quantity');
        $sum_price = 0;
        // В случае если корзина будет пуста, количество и общая стоимость корзины будет нулевая
        // Но чтобы посчитать стоимость каждого продукта с учетом его количества
        // Берем корзину и перебираем в нем продукты, при этом умножаем стоимость на количество каждого товара
        // Прибавляем к сумме стоимости корзины
        foreach ($cart as $item) {
            $sum_price += $item['price_product'] * $item['quantity'];
        }

        return response([
           'cart' => $cart,
           'sum_quantities' => $sum_quantities,
            'sum_price' => $sum_price
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  int  $id
     */
    public function store($id)
    {
        // Вытаскиваем id авторизованного пользователя
        // И ищем продукт через модельку и проверяем через ее поля имеется ли такой продукт
        // В случае если находим уже имеющийся продукт, просто приписываем количество
        // Иначе добавляем в корзину продукт которого нету
        $user = \auth()->id();
        $product = Product::findOrFail($id);
        if ($cart = Cart::where(['user_id' => $user,'product_id' => $product->id])->first()) {
            $cart->quantity++;
            $cart->save();
        }
        else {
            $cart = Cart::create([
                "user_id" => $user,
                "product_id" => $product->id,
                "price_product" => $product->price,
                "quantity" => 1,
            ]);
        }

        $response = [
            'cart' => $cart,
        ];
        return response($response, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
       // Удаляем по пришедшему с запроса id продукт из корзины
        Cart::destroy($id);
        return response([
            'message' => 'Deleted!'
        ]);
    }
}
```

Поработаем в ресурсном контроллере `OrderController`<br>

```php
   class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Забираем id авторизованного пользователя
        // Показываем список всех заказов которые были оформлены
        // Процесс аналогичен корзине товаров
        $user = \auth()->id();
        $order = Order::where(['user_id' => $user])->get();
        $sum_quantities = Order::where(['user_id' => $user])->sum('quantity');
        $sum_price = 0;
        foreach ($order as $item) {
            $sum_price += $item['price_product'] * $item['quantity'];
        }

        return response([
            'order' => $order,
            'order_sum_quantities' => $sum_quantities,
            'order_sum_price' => $sum_price
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store()
    {
        // Забираем id авторизованного пользователя
        // Переписываем корзину и добавляем ее в лист заказов
        $user = \auth()->id();
        $cart = Cart::where(['user_id' => $user])->first();
        $order = Order::create([
            "user_id" => $user,
            "product_id" => $cart->product_id,
            "price_product" => $cart->price_product,
            "quantity" => $cart->quantity,
        ]);
        // Добавляем id номер заказа и сам лист заказа
        $response = [
            'id_order' => $order->id,
            'order' => $order
        ];
        // Удаляем корзину авторизованного пользователя т.к уже оформили заказ
        Cart::destroy($user);

        return response($response, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
```

И последнее, что следует из всего этого это маршрутизация. <br>
Прыгаем в `routes/api.php`. Регистрируем новые роуты.
```php
   
   // Это общедоступные методы которые доступны и гостю, и админу, и пользователю
   // Здесь обычные роуты которые используют методы как ресурсного контроллера
   // Так и методы обычного контроллера авторизации

   // GENERAL ROUTES
   Route::get('/products', [ProductController::class, 'index']);
   Route::post('/signup', [AuthController::class, 'register']);
   Route::post('/login', [AuthController::class, 'login']);

   // Здесь мы создаем группу роутов которая защищена middleware [sanctum]
   // И это дает возможность работать с данными роутами только авторизованным пользователям
   // Тоесть пользователям, у которых имеется Bearer token!
   // В случае попытки использования роутов гостем
   // Приведет к ответу от сервера - Неавторизован!
 
   // PROTECTED ROUTES
   Route::group(['middleware' => 'auth:sanctum'], function () {
       Route::get('/logout', [AuthController::class, 'logout']);
       Route::get('/cart', [CartController::class, 'index']);
       Route::post('/cart/{id}', [CartController::class, 'store']);
       Route::delete('/cart/{id}', [CartController::class, 'destroy']);
       Route::post('/order', [OrderController::class, 'store']);
       Route::get('/order', [OrderController::class, 'index']);
   });
   
   // Мы создавали ранее middleware для проверки на админа
   // Но проблема в том что если мы будет использовать только один middleware
   // То неавторизованный пользователь получит доступ к ограниченным роутам
   // Даже в случае если он является админом, что протеворечит здравому смыслу.
   // Поэтому в группе мы записываем два middleware (админский и middleware от [auth:sanctum])
   // Теперь если мы не авторизуемся но у нас будет имется статус админа
   // Все равно мы не получим к данным роутам доступ
   
   // PROTECTED ROUTES OF ADMIN
   Route::group(['middleware' => ['admin', 'auth:sanctum']], function () {
       Route::post('/products', [ProductController::class, 'store']);
       Route::put('/product/{id}', [ProductController::class, 'update']);
       Route::delete('/product/{id}', [ProductController::class, 'destroy']);
   });
```

Теперь можно открыть postman коллекцию которая находится в корне проекта. <br>
Тестируем!

## Лицензия [MIT license](https://opensource.org/licenses/MIT).

