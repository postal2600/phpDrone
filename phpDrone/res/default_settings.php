#Hacking attempt<?php /* #DON'T REMOVE PHP TAGS ?>
[Main]
#cacheDir = ./tmpl_cache
debugMode = True
#compressHTML = True
codeLanguage = en
appMode = development


[Modules]
utils = true #Rrequired
database = true
form = true
template = true
mail = true
widgets = true
i18n = true
admin = true
profiler = true
controller = false

[Database] #This options will work only if 'database' module is loaded
sqlEngine = mysql
sqlServer= localhost
sqlUser= user
sqlPassword= password
sqlDatabase= databaseName


[Admin] #This options will work only if 'admin' module is loaded
user =
pass =
#passHash =


[SMTP] #This options will work only if 'mail' module is loaded
#host =
#port =
#username =
#password =


[Controller] #This options will work only if 'controller' module is loaded
defaultController = index
defaultMethod = index

;<?php #DON'T REMOVE PHP TAGS*/ ?>
