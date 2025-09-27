# ParseError - Internal Server Error
syntax error, unexpected token "public", expecting end of file

PHP 8.4.12
Laravel 12.31.1
localhost

## Stack Trace

0 - app/Services/ReferralService.php:125
1 - vendor/composer/ClassLoader.php:427
2 - vendor/laravel/framework/src/Illuminate/Container/Container.php:1161
3 - vendor/laravel/framework/src/Illuminate/Container/Container.php:411
4 - vendor/laravel/framework/src/Illuminate/Container/Container.php:1154
5 - vendor/laravel/framework/src/Illuminate/Container/Container.php:972
6 - vendor/laravel/framework/src/Illuminate/Foundation/Application.php:1078
7 - vendor/laravel/framework/src/Illuminate/Container/Container.php:903
8 - vendor/laravel/framework/src/Illuminate/Foundation/Application.php:1058
9 - vendor/laravel/framework/src/Illuminate/Support/Facades/Facade.php:363
10 - app/Models/User.php:79
11 - app/Models/User.php:62
12 - vendor/laravel/framework/src/Illuminate/Events/Dispatcher.php:488
13 - vendor/laravel/framework/src/Illuminate/Events/Dispatcher.php:315
14 - vendor/laravel/framework/src/Illuminate/Events/Dispatcher.php:295
15 - vendor/laravel/framework/src/Illuminate/Database/Eloquent/Concerns/HasEvents.php:224
16 - vendor/laravel/framework/src/Illuminate/Database/Eloquent/Model.php:1422
17 - vendor/laravel/framework/src/Illuminate/Database/Eloquent/Model.php:1240
18 - vendor/filament/filament/src/Resources/Pages/CreateRecord.php:191
19 - vendor/filament/filament/src/Resources/Pages/CreateRecord.php:102
20 - vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php:36
21 - vendor/laravel/framework/src/Illuminate/Container/Util.php:43
22 - vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php:96
23 - vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php:35
24 - vendor/livewire/livewire/src/Wrapped.php:23
25 - vendor/livewire/livewire/src/Mechanisms/HandleComponents/HandleComponents.php:492
26 - vendor/livewire/livewire/src/Mechanisms/HandleComponents/HandleComponents.php:101
27 - vendor/livewire/livewire/src/LivewireManager.php:102
28 - vendor/livewire/livewire/src/Mechanisms/HandleRequests/HandleRequests.php:94
29 - vendor/laravel/framework/src/Illuminate/Routing/ControllerDispatcher.php:46
30 - vendor/laravel/framework/src/Illuminate/Routing/Route.php:265
31 - vendor/laravel/framework/src/Illuminate/Routing/Route.php:211
32 - vendor/laravel/framework/src/Illuminate/Routing/Router.php:822
33 - vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php:180
34 - vendor/laravel/framework/src/Illuminate/Routing/Middleware/SubstituteBindings.php:50
35 - vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php:219
36 - vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/VerifyCsrfToken.php:87
37 - vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php:219
38 - vendor/laravel/framework/src/Illuminate/View/Middleware/ShareErrorsFromSession.php:48
39 - vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php:219
40 - vendor/laravel/framework/src/Illuminate/Session/Middleware/StartSession.php:120
41 - vendor/laravel/framework/src/Illuminate/Session/Middleware/StartSession.php:63
42 - vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php:219
43 - vendor/laravel/framework/src/Illuminate/Cookie/Middleware/AddQueuedCookiesToResponse.php:36
44 - vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php:219
45 - vendor/laravel/framework/src/Illuminate/Cookie/Middleware/EncryptCookies.php:74
46 - vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php:219
47 - vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php:137
48 - vendor/laravel/framework/src/Illuminate/Routing/Router.php:821
49 - vendor/laravel/framework/src/Illuminate/Routing/Router.php:800
50 - vendor/laravel/framework/src/Illuminate/Routing/Router.php:764
51 - vendor/laravel/framework/src/Illuminate/Routing/Router.php:753
52 - vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php:200
53 - vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php:180
54 - vendor/livewire/livewire/src/Features/SupportDisablingBackButtonCache/DisableBackButtonCacheMiddleware.php:19
55 - vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php:219
56 - vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/ConvertEmptyStringsToNull.php:27
57 - vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php:219
58 - vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TrimStrings.php:47
59 - vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php:219
60 - vendor/laravel/framework/src/Illuminate/Http/Middleware/ValidatePostSize.php:27
61 - vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php:219
62 - vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/PreventRequestsDuringMaintenance.php:109
63 - vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php:219
64 - vendor/laravel/framework/src/Illuminate/Http/Middleware/HandleCors.php:48
65 - vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php:219
66 - vendor/laravel/framework/src/Illuminate/Http/Middleware/TrustProxies.php:58
67 - vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php:219
68 - vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/InvokeDeferredCallbacks.php:22
69 - vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php:219
70 - vendor/laravel/framework/src/Illuminate/Http/Middleware/ValidatePathEncoding.php:26
71 - vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php:219
72 - vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php:137
73 - vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php:175
74 - vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php:144
75 - vendor/laravel/framework/src/Illuminate/Foundation/Application.php:1220
76 - public/index.php:20
77 - vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php:23

## Request

POST /livewire/update

## Headers

* **host**: localhost
* **connection**: keep-alive
* **content-length**: 1632
* **sec-ch-ua-platform**: "Windows"
* **user-agent**: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36
* **sec-ch-ua**: "Chromium";v="140", "Not=A?Brand";v="24", "Google Chrome";v="140"
* **content-type**: application/json
* **x-livewire**: 
* **sec-ch-ua-mobile**: ?0
* **accept**: */*
* **origin**: http://localhost
* **sec-fetch-site**: same-origin
* **sec-fetch-mode**: cors
* **sec-fetch-dest**: empty
* **referer**: http://localhost/admin/users/create
* **accept-encoding**: gzip, deflate, br, zstd
* **accept-language**: en-US,en;q=0.9
* **cookie**: XDEBUG_SESSION=YOUR-NAME; adminer_permanent=c2VydmVy--cm9vdA%3D%3D-bG9jYWw%3D%3Al9thPi9qaPQVGA2SMS5VzMn95UQqbv8JTepLow63RGOJYkc6rl6lFpv7nUb1i35JmANC4Q6LNjLXDU3dcuHHSUlwb3QapkKsnHIsxbQCBbcrCcN7VaAfv0yIvasb1egp; authjs.session-token=eyJhbGciOiJkaXIiLCJlbmMiOiJBMjU2Q0JDLUhTNTEyIiwia2lkIjoiR3Y3VVlTT1VyRjZlZTN4Yjc5ODF2eTUxOFg5bHpIX0dPRXpwdGp4ejdYUnBEWjFMUDQzbDBaMHdNVm5DU1Vpc01LdWtVTF9lNHk1ak1LZG9mQTZadkEifQ..0bm7yk4uXirOi0DyVt0Oww.gWoddPEutaGuiWcZKw8Udv-xaipVZYhw2mUSGH21w3msujAqWbNdAiyW4wtLmI4FXADuFznc6q2ZEALNtUxqdbuFKlJbgCbGFVp6symKZ8CebhkzDHAVLSqKs4GtySCR9Rdepi1apFOn1GQVDAlEbfZezGq58evOhkIo0_g_4dnUgaMjzCLUpzGl1D6gBm6puaAQdxXe-eTpszSqN15ObsrwnfO1tL58ETZ9_M65ZJg._l3QBqzl5ApRrRGjrTowTLqNKYZuzdq7I0XiSIzD_7I; remember_web_59ba36addc2b2f9401580f014c7f58ea4e30989d=eyJpdiI6IjdUM0xyeGxDcCt6YTRqWTJwNEx0YVE9PSIsInZhbHVlIjoiSVY2YTVueHQ1V0dPTmJmc2JNbUZJcDdaTk1KNXh5OVRPT3dhbjNQeFhzR3diUjRrK1hia21ycC9Xajh6US9UbFJKeS80TWROZ1dhb2daWlk2bUZEREIyYXl2MUd4VURKbm5nZExLNFBYRVNheGRGMVVxb01sckVSdmY1Wk1sdXk1NUt2MFRwc293eWpKMGIwMHZvYmRtVFlOSDlKRjlRYVVLY0lPenJJVk02UTRSTC85T3dwVHBBZFpNcEZXSUNxRjRLdUlya0RvVWlrMDFsUlUxem1XNDJCSFN4TEtGS0IxSUdBcEUzYXhIND0iLCJtYWMiOiJmNDFkNGVhMmRlNTI3N2Y4OTdmOWZjYTY1ZDQxZGYwYTRhZDMwOTVkYzc0NWEyZDllNzQxYTdjYTNkZDY2MzNlIiwidGFnIjoiIn0%3D; XSRF-TOKEN=eyJpdiI6ImZWa3F6WVRWTkRqZnlYN3ZNc0FnbUE9PSIsInZhbHVlIjoiSVEyUTVJTmZRajBxRHNEUHVja242K0prMGNzSUJ0MHBPNnk3RmJhYTgxeEdmTHNFNFBSQWZITXB3ZkRkajM2b05sQlBqTXBsQUJoSnl4ekF2bExJckdCZCtCTkVCbHNKWlNLbDhkemtHdjkwODA0N3pPVlNQSDI0K1c2MmxPcHEiLCJtYWMiOiI2OTdmNDQxMzVjNWQwNjc4M2RlZWNjYmNkMTMyZGQ0NTQyN2EwNGI3MmY2MTY5OGJiZmY2YjFlNmJkMjZkNWJkIiwidGFnIjoiIn0%3D; laravel-session=eyJpdiI6IjVVV3ZvZlJHZDRjSndEOHdHeHV5VGc9PSIsInZhbHVlIjoiekR6VmFmcndGTVhxN2o4dTdnMTkrdWlrcDhsQnpDMndPOE4vcDF6ekdHbE03OHhWRDQxb0pkZ3ljL0RUTW0yU29xVG9aZlc2RVpoM2h2K2JjWEFIdWI3enpMVW5nRGVNemVqQWViVU83ZTM5TFowZkFmZUN3eElISHNMK1BBUEEiLCJtYWMiOiI0NzBlYjZkZGNkNzI2ZjIyOWIwZDc3OTA4YmJmYzc2OWY1Y2QxMzZiZjE3OGUyM2Y4ZTE1MGZmYjg0OWNlNGRiIiwidGFnIjoiIn0%3D

## Route Context

controller: Livewire\Mechanisms\HandleRequests\HandleRequests@handleUpdate
route name: livewire.update
middleware: web

## Route Parameters

No route parameter data available.

## Database Queries

* mysql - select * from `cache` where `key` in ('laravel-cache-telescope:dump-watcher') (2.99 ms)
* mysql - select * from `sessions` where `id` = 'rXf7Bvamu6FbX3R0VJrLoLEIqD1P5Qmp0D8vaRo8' limit 1 (0.69 ms)
* mysql - select * from `users` where `id` = 2 limit 1 (1.15 ms)
* mysql - select count(*) as aggregate from `users` where `email` = 'yuhhh@example.com' (0.86 ms)
* mysql - insert into `users` (`name`, `email`, `password`, `updated_at`, `created_at`) values ('yuhhh', 'yuhhh@example.com', 'y$Jd4b5jJ71yMYUOO4kExgDuWYPE05eGk6J6ggkCK3gV50zjwtMwVnW', '2025-09-27 04:40:55', '2025-09-27 04:40:55') (16.03 ms)
