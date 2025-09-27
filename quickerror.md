Internal Server Error

Copy as Markdown
Illuminate\Database\QueryException
SQLSTATE[42S02]: Base table or view not found: 1146 Table 'laravel.custom_fields' doesn't exist (Connection: mysql, SQL: select count(*) as aggregate from `custom_fields`)

LARAVEL
12.31.1
PHP
8.4.12
UNHANDLED
CODE 42S02
GET
http://localhost/admin/custom-fields

Overview
DATE
2025/09/27 04:19:36.847 UTC
STATUS CODE
500
METHOD
GET
Exception trace
97 vendor frames

public/index.php
public/index.php:20


// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());

1 vendor frame

Queries
1-4 of 4
mysql
select * from `cache` where `key` in ('laravel-cache-telescope:dump-watcher')
5.52ms
mysql
select * from `cache` where `key` in ('laravel-cache-telescope:pause-recording')
1.11ms
mysql
select * from `sessions` where `id` = 'rXf7Bvamu6FbX3R0VJrLoLEIqD1P5Qmp0D8vaRo8' limit 1
0.95ms
mysql
select * from `users` where `id` = 2 limit 1
1.52ms
Headers
host
localhost
connection
keep-alive
sec-ch-ua
"Chromium";v="140", "Not=A?Brand";v="24", "Google Chrome";v="140"
sec-ch-ua-mobile
?0
sec-ch-ua-platform
"Windows"
upgrade-insecure-requests
1
user-agent
Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36
accept
text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7
sec-fetch-site
none
sec-fetch-mode
navigate
sec-fetch-user
?1
sec-fetch-dest
document
accept-encoding
gzip, deflate, br, zstd
accept-language
en-US,en;q=0.9
cookie
XDEBUG_SESSION=YOUR-NAME; adminer_permanent=c2VydmVy--cm9vdA%3D%3D-bG9jYWw%3D%3Al9thPi9qaPQVGA2SMS5VzMn95UQqbv8JTepLow63RGOJYkc6rl6lFpv7nUb1i35JmANC4Q6LNjLXDU3dcuHHSUlwb3QapkKsnHIsxbQCBbcrCcN7VaAfv0yIvasb1egp; authjs.session-token=eyJhbGciOiJkaXIiLCJlbmMiOiJBMjU2Q0JDLUhTNTEyIiwia2lkIjoiR3Y3VVlTT1VyRjZlZTN4Yjc5ODF2eTUxOFg5bHpIX0dPRXpwdGp4ejdYUnBEWjFMUDQzbDBaMHdNVm5DU1Vpc01LdWtVTF9lNHk1ak1LZG9mQTZadkEifQ..0bm7yk4uXirOi0DyVt0Oww.gWoddPEutaGuiWcZKw8Udv-xaipVZYhw2mUSGH21w3msujAqWbNdAiyW4wtLmI4FXADuFznc6q2ZEALNtUxqdbuFKlJbgCbGFVp6symKZ8CebhkzDHAVLSqKs4GtySCR9Rdepi1apFOn1GQVDAlEbfZezGq58evOhkIo0_g_4dnUgaMjzCLUpzGl1D6gBm6puaAQdxXe-eTpszSqN15ObsrwnfO1tL58ETZ9_M65ZJg._l3QBqzl5ApRrRGjrTowTLqNKYZuzdq7I0XiSIzD_7I; remember_web_59ba36addc2b2f9401580f014c7f58ea4e30989d=eyJpdiI6IjdUM0xyeGxDcCt6YTRqWTJwNEx0YVE9PSIsInZhbHVlIjoiSVY2YTVueHQ1V0dPTmJmc2JNbUZJcDdaTk1KNXh5OVRPT3dhbjNQeFhzR3diUjRrK1hia21ycC9Xajh6US9UbFJKeS80TWROZ1dhb2daWlk2bUZEREIyYXl2MUd4VURKbm5nZExLNFBYRVNheGRGMVVxb01sckVSdmY1Wk1sdXk1NUt2MFRwc293eWpKMGIwMHZvYmRtVFlOSDlKRjlRYVVLY0lPenJJVk02UTRSTC85T3dwVHBBZFpNcEZXSUNxRjRLdUlya0RvVWlrMDFsUlUxem1XNDJCSFN4TEtGS0IxSUdBcEUzYXhIND0iLCJtYWMiOiJmNDFkNGVhMmRlNTI3N2Y4OTdmOWZjYTY1ZDQxZGYwYTRhZDMwOTVkYzc0NWEyZDllNzQxYTdjYTNkZDY2MzNlIiwidGFnIjoiIn0%3D; XSRF-TOKEN=eyJpdiI6IjBoUHMrZ1RGNy9mMHQ4TnpEaWgyTXc9PSIsInZhbHVlIjoiREllR2wxWE5EVldDNnhJQkcySzJ1NFlzays2YkRkVXY3QVYrQ2kwZkRzdW1vTlZaYXJ6T015NXcraFZMUVltMGtxTGNSY21Kbkg0aENGdys1eklIeFRVZzVPWE5uV1ZSR2xnaCtYUUR4b2wxMFdvQzNoQkNSYmJkdDBTcU1EbXkiLCJtYWMiOiIyOWQ0Y2UyZDcwMmFjYjA0NmZkMTBmOTRiN2UxOTE2NWNhNDViYTQxZDE3MzA2YzVjOTA3OTIwMTFjY2JkNDMwIiwidGFnIjoiIn0%3D; laravel-session=eyJpdiI6Ik1Ja3k1WGp1dGZ4QithbVNJU3lPZUE9PSIsInZhbHVlIjoiVmtNZVgxVmxZNmxRRnB5OUdDaitJY1ZrTXh6ck5mNUpqV0RKa3JaOGlhWFdHMVROejdkZUdFbGc5OHFVcU5mcjNtUXNlVzhjY3BOVWw2QWNJM1d5RGpxT3duSGpWeU02Y2tmQWpPc2VrTkptUFAwVWRTTGJOTGJFQUdIUncvbEQiLCJtYWMiOiJhNDM2MjdlZWExYzdlZjU4YTkyMDUwMzY4NTUxMTdjNGQyMTA0YjdkMTFmNDI4ZTA0ODYxNDU0MzBmYTk1NWU3IiwidGFnIjoiIn0%3D
Body
// No request body
Routing
controller
App\Filament\Resources\CustomFieldResource\Pages\ListCustomFields
route name
filament.admin.resources.custom-fields.index
middleware
panel:admin, Illuminate\Cookie\Middleware\EncryptCookies, Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse, Illuminate\Session\Middleware\StartSession, Filament\Http\Middleware\AuthenticateSession, Illuminate\View\Middleware\ShareErrorsFromSession, Illuminate\Foundation\Http\Middleware\VerifyCsrfToken, Illuminate\Routing\Middleware\SubstituteBindings, Filament\Http\Middleware\DisableBladeIconComponents, Filament\Http\Middleware\DispatchServingFilamentEvent, Filament\Http\Middleware\Authenticate
Routing parameters
// No routing parameters
