# Schematics
Плагин для управления схемами блоков
* Экспорт - сохраняет выделенную область блоков в схему (файл .schematic) в папку плагина. В схему указываются координаты, айди и мета блока. Игнорирует: воздух, трава, земля, сундук, печь. Файл .schematic является сериализованным массивом php и может быть использован только этим плагином. Это не настоящая схема.
* Импорт - импорирует список блоков из схемы и ставит их в текущий мир по тем же координатам, где они были при записи схемы.
>Внимание: плагин не может поставить большое количество блоков, чанки не генерируются сами. Я правда не знаю, почему так происходит. Для импорта большой области необходимо сначала >импортировать схему единожды, затем перемещаться туда, где должны быть её остатки и вводить команду ещё раз.
___
Плагин не сильно полезный, писал для себя, чтобы починить старый мир с ПК. На код не смотрите, там грязно.
## Команды
* **/schem pos1** - установить позицию 1
* **/schem pos2** - установить позицию 2
* **/schem list** - показать список схем
* **/schem export** <имя> - экспортировать область в схему
* **/schem import** <имя> - импортировать схему из файла и поставить блоки в текущий мир
* **/schem remove** <имя> - удалить схему
## Разрешения
* schematics.use:
default: op
## Использование
Выделите область двумя позициями, как в привате (игнорируя воздух, землю и траву, они НЕ записываются в схему) и сохраните её командой /schem export
## Применение
* Позволяет восстановить область, например спавн в случае, если он был повреждён. Или другую постройку.
* Позволяет извлечь из сломанного мира схему блоков, а затем вставить эти блоки в чистый, сгенерированный ядром мир. Полезно при портировании карт с ПК, особенно плоских. После импорта нет багов со светом, нет сущностей и тайлов. Только блоки.
