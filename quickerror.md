Internal Server Error

Copy as Markdown
Spatie\LaravelSettings\Exceptions\MissingSettings
Tried saving settings 'App\Settings\GeneralSettings', and the following properties were missing: frontendUrl, supportEmail, welcomeRewardProductId, referralSignupGiftId, referralBannerText, pointsName, rankName, welcomeHeaderText, scanButtonCta

LARAVEL
12.31.1
PHP
8.4.12
UNHANDLED
CODE 0
POST
http://localhost/livewire/update

Overview
DATE
2025/09/27 04:00:41.533 UTC
STATUS CODE
500
METHOD
POST
Exception trace
4 vendor frames

app/Filament/Pages/ManageSettings.php
app/Filament/Pages/ManageSettings.php:82

                $settings->{$key} = $value;
            }
        }
        
        // Save the settings
        $settings->save();
        
        $this->getSavedNotification()?->send();
    }
}

56 vendor frames

public/index.php
public/index.php:20

1 vendor frame

Queries
1-6 of 6
mysql
select * from `cache` where `key` in ('laravel-cache-telescope:dump-watcher')
4.16ms
mysql
select * from `sessions` where `id` = 'rXf7Bvamu6FbX3R0VJrLoLEIqD1P5Qmp0D8vaRo8' limit 1
0.75ms
mysql
select * from `users` where `id` = 2 limit 1
0.94ms
mysql
select * from `products`
0.9ms
mysql
select * from `products`
0.35ms
mysql
select `name`, `payload` from `settings` where `group` = 'general'
0.81ms
Headers
host
localhost
connection
keep-alive
content-length
1574
sec-ch-ua-platform
"Windows"
user-agent
Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36
sec-ch-ua
"Chromium";v="140", "Not=A?Brand";v="24", "Google Chrome";v="140"
content-type
application/json
x-livewire
sec-ch-ua-mobile
?0
accept
*/*
origin
http://localhost
sec-fetch-site
same-origin
sec-fetch-mode
cors
sec-fetch-dest
empty
referer
http://localhost/admin/manage-settings
accept-encoding
gzip, deflate, br, zstd
accept-language
en-US,en;q=0.9
cookie
XDEBUG_SESSION=YOUR-NAME; adminer_permanent=c2VydmVy--cm9vdA%3D%3D-bG9jYWw%3D%3Al9thPi9qaPQVGA2SMS5VzMn95UQqbv8JTepLow63RGOJYkc6rl6lFpv7nUb1i35JmANC4Q6LNjLXDU3dcuHHSUlwb3QapkKsnHIsxbQCBbcrCcN7VaAfv0yIvasb1egp; authjs.session-token=eyJhbGciOiJkaXIiLCJlbmMiOiJBMjU2Q0JDLUhTNTEyIiwia2lkIjoiR3Y3VVlTT1VyRjZlZTN4Yjc5ODF2eTUxOFg5bHpIX0dPRXpwdGp4ejdYUnBEWjFMUDQzbDBaMHdNVm5DU1Vpc01LdWtVTF9lNHk1ak1LZG9mQTZadkEifQ..0bm7yk4uXirOi0DyVt0Oww.gWoddPEutaGuiWcZKw8Udv-xaipVZYhw2mUSGH21w3msujAqWbNdAiyW4wtLmI4FXADuFznc6q2ZEALNtUxqdbuFKlJbgCbGFVp6symKZ8CebhkzDHAVLSqKs4GtySCR9Rdepi1apFOn1GQVDAlEbfZezGq58evOhkIo0_g_4dnUgaMjzCLUpzGl1D6gBm6puaAQdxXe-eTpszSqN15ObsrwnfO1tL58ETZ9_M65ZJg._l3QBqzl5ApRrRGjrTowTLqNKYZuzdq7I0XiSIzD_7I; remember_web_59ba36addc2b2f9401580f014c7f58ea4e30989d=eyJpdiI6IjdUM0xyeGxDcCt6YTRqWTJwNEx0YVE9PSIsInZhbHVlIjoiSVY2YTVueHQ1V0dPTmJmc2JNbUZJcDdaTk1KNXh5OVRPT3dhbjNQeFhzR3diUjRrK1hia21ycC9Xajh6US9UbFJKeS80TWROZ1dhb2daWlk2bUZEREIyYXl2MUd4VURKbm5nZExLNFBYRVNheGRGMVVxb01sckVSdmY1Wk1sdXk1NUt2MFRwc293eWpKMGIwMHZvYmRtVFlOSDlKRjlRYVVLY0lPenJJVk02UTRSTC85T3dwVHBBZFpNcEZXSUNxRjRLdUlya0RvVWlrMDFsUlUxem1XNDJCSFN4TEtGS0IxSUdBcEUzYXhIND0iLCJtYWMiOiJmNDFkNGVhMmRlNTI3N2Y4OTdmOWZjYTY1ZDQxZGYwYTRhZDMwOTVkYzc0NWEyZDllNzQxYTdjYTNkZDY2MzNlIiwidGFnIjoiIn0%3D; XSRF-TOKEN=eyJpdiI6ImIvVGVHZHRwcGZHbVJUQ2sxalZoZWc9PSIsInZhbHVlIjoiSGwwVHZTaVFHNlcwMlkrY0tWZW5VT0NId1NSTTE5RHpQallWZjhMY3hSZFEvNG1JRFJUMTJ2YzFRQzBuSEdtbXpwUWpkSjdaOVBkbWtvNDIzVVRsMTRZMlFoVkxkaXdJcHlXejlQSFJQMHhFUzBPL1FLSjJ3Nkp1Mm8rQTNhdVIiLCJtYWMiOiI1ZTllNTdiOTI4YTFkY2NlMzRiYWZmMDEzYTZhYzU3M2Q5ZDM4ZWFiZTdlODk0MmFhNzhmYTNjODAyMzY5OTdhIiwidGFnIjoiIn0%3D; laravel-session=eyJpdiI6IjBoVVRjZGlSa2RMZDZ1em4xZ2VZc1E9PSIsInZhbHVlIjoicjBPcVc4THdITWhSdzVMVE9jOTltd1Vaajg4emp6L2FNUGFpNE1wWk1Hd2Nhb1JyZnFuMUtOTHNRMHp6OWFBa1lYOUFRTDkrUkcwUVpkbWw0aGYxR255R1RUbmZ4R0hIaDFpcGJaclZqMFgwYXUxczNmZlI5L1dra2p3azQyQVkiLCJtYWMiOiJhMTdiZTY1NGY1MjBkMjg1ZTM4Njg3MGM4ZWM1N2FhYjQ2NjIzZWVhZjVkYzYxNGNjMWQzMjlkZmI1ZWQxM2FhIiwidGFnIjoiIn0%3D
Body
{
    "_token": "6ON1yPnAITd9WDXXUnO32CPWTtpqZHoWx0Reyl7m",
    "components": [
        {
            "snapshot": "{"data":{"data":[{"frontendUrl":"http://localhost","supportEmail":"support@example.com","welcomeRewardProductId":null,"referralSignupGiftId":null,"referralBannerText":"ud83cudf81 Earn More By Inviting Your Friends","pointsName":"Points","rankName":"Rank","welcomeHeaderText":"Welcome, {firstName}","scanButtonCta":"Scan Product"},{"s":"arr"}],"mountedActions":[[],{"s":"arr"}],"mountedActionsArguments":[[],{"s":"arr"}],"mountedActionsData":[[],{"s":"arr"}],"defaultAction":null,"defaultActionArguments":null,"componentFileAttachments":[[],{"s":"arr"}],"areFormStateUpdateHooksDisabledForTesting":false,"mountedFormComponentActions":[[],{"s":"arr"}],"mountedFormComponentActionsArguments":[[],{"s":"arr"}],"mountedFormComponentActionsData":[[],{"s":"arr"}],"mountedFormComponentActionsComponents":[[],{"s":"arr"}],"mountedInfolistActions":[[],{"s":"arr"}],"mountedInfolistActionsData":[[],{"s":"arr"}],"mountedInfolistActionsComponent":null,"mountedInfolistActionsInfolist":null,"savedDataHash":null},"memo":{"id":"DwXe4o10WtgEbstMjb9E","name":"app.filament.pages.manage-settings","path":"admin/manage-settings","method":"GET","children":[],"scripts":[],"assets":[],"errors":[],"locale":"en"},"checksum":"02f078ce6dc32c13af2fbb98c4523c5c83aa8ad317cd393abbb122df3d6efc9b"}",
            "updates": [],
            "calls": [
                {
                    "path": "",
                    "method": "save",
                    "params": []
                }
            ]
        }
    ]
}
Routing
controller
Livewire\Mechanisms\HandleRequests\HandleRequests@handleUpdate
route name
livewire.update
middleware
web
Routing parameters
// No routing parameters
