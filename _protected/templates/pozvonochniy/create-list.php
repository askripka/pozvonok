<div class="m-h-md">
    <button class="btn btn-primary" id="showListForm"><i class="fa fa-plus"></i> Добавить список</button>
    <button style="display: none;" class="btn btn-primary" id="hideListForm"><i class="fa fa-minus"></i> Скрыть форму
    </button>
</div>


<div class="widget" id="listForm" style="display:none;">
    <header class="widget-header">
        <h4 class="widget-title">Импортировать контакты и создать список</h4>
    </header>
    <hr class="widget-separator">
    <div class="widget-body">
        <form class="form-horizontal form-ajax <?= !$list ? 'sisiph' : ''; ?>" action="/ajax.php" method="POST" enctype="multipart/form-data">

            <? if($list): ?>
                <input type="hidden" name="update_list" value="1"/>
                <input type="hidden" name="id" value="<?=$list['id']?>"/>
            <? else: ?>
                <input type="hidden" name="create_list" value="1"/>
            <? endif; ?>

            <div class="form-group">
                <label class="col-sm-3 control-label">Название списка</label>
                <div class="col-sm-6">
                    <input name="title" type="text" class="form-control" value="<?=$list['title']?>" required>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-3 control-label">Файл импорта</label>
                <div class="col-sm-6">
                    <input name="import" type="file" class="form-control" required>
                    <span class="help-block">
                        Распознаваемые форматы номеров (только Россия): +7[10цифр], 7[10цифр], 8[10цифр], [10цифр]. Скобки, черточки в номерах удаляются.<br/>
                        Макс. размер  файла: 2Мб<br>
                        Распознаваемые форматы файлов: XLS, XLSX, CSV, TXT<br>
                        Распознаваемые разделители столбцов: ";" либо ","<br>
                        Распознаваемые кодировки: utf-8, windows-1251, koi8-r, iso8859-5<br>
                        Если в документе есть два столбца и один с именами контактов, он будет так же распознан.<br>
                        Если файл не соответсвует этим условиям, он не будет корректно распознан.<br>
                    </span>
                </div>
            </div>

            <div role="alert" class="alert alert-warning alert-dismissible form-ajax-message-wrapper">
                <button aria-label="Close" data-dismiss="alert" class="close" type="button">
                    <span aria-hidden="true">×</span></button>
                <span class="form-ajax-message"></span>
            </div>

            <div class="form-group">
                <div class="col-sm-8 col-sm-offset-4">
                    <button id="taskStart" class="btn btn-primary" type="submit">
                        <i class="fa fa-save"></i> Импортировать файл и создать список
                    </button>
                </div>
            </div>

        </form>


    </div>
</div>
