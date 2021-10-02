Zippy Store
========
Программа  складского  учета  с  веб интерфейсом. 
Предназначена для использования малым бизнесом с упрощенной формой учета, который не использует полноценный бухгалтерский учет. 
   
Домашняя страница:  [https://zippy.com.ua/zstore](https://zippy.com.ua/zstore)  

####
 Основная  функциональность
 
* управление складами, складская логистика 
* закупка 
* продажа 
* учет курса валют при закупке и продаже 
* учет платежей и взаиморасчеты с котрагентами 
* партионный учет и учет по сериям производителя 
* управление пользователями и доступом, личный кабинет пользователя 
* работа  с  лидами  и друние  элементы элементы CRM 
* отчеты по продажам, закупкам, движению товара 
* услуги, задачи, календарь выполнения работ 
* учет оборудования 
* API для обмена с другими информационными системами, например интернет-магазином написаном на другой платформе. 
* поддержка сканера (клавиатурного) штрихкода . 
* поддержка принтеров чеков и этикеток . 
* разделение доступа между филиалами (например торговыми точками) 
* модуль интеграции с  Опенкарт 
* модуль интеграции с  Woocomerce 
* интеграция  с  Новой  Почтой
* интеграция  с  сервисами  смс  рассылок
* модуль  для  общепита
* расчет зарплаты
* производство


Требования: PHP7.2+    Mysql 5.7+ 


Установка  системы.
--------------------

  Создать  БД (кодировка  utf8_general_ci), выполнить  SQL скрипты (папка DB) сначала  структуру db.sql  потом  данные  инициализации initdata.sql.
  Файлы  update*.sql  для новой  БД  выполнять не  нужно.

  Скопировать  содержимое  папки  www   в   корневой   каталог  сайта. 
  Выполнить composer.json для   скачивания   сторонних библиотек .
  
  Конфигурационные  файлы  лежат в  папке   config.

  Установить параметры соединения с  БД  в  файле config.ini.  
   
  Также  необходимо убедиться  что  разрешено  право  записи  в папки  uploads и logs. 

  Залогиниться  дефолтным  пользователем admin  admin
