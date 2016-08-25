<?php
require_once __DIR__.'/../_protected/api.php';
$cache = new CACHE();
$api = new API();

if(!API::is_adminpanel()) {
    exit();
}

if($_POST['add_page']) {
    unset($_POST['add_page']);
    $api->insert_page($_POST);
}

if($_POST['edit_page']) {
    unset($_POST['edit_page']);
    $api->update_page($_POST);
}

if($_POST['delete_page']) {
    unset($_POST['delete_page']);
    $api->delete_page($_POST['node1'], $_POST['node2'], $_POST['node3']);
}

if($_POST['add_element']) {
    unset($_POST['add_element']);
    $api->insert_element($_POST);
}


if($_POST['show_element']) {
    $api->update_element_visibility($_POST['id'], 1);
}

if($_POST['hide_element']) {
    $api->update_element_visibility($_POST['id'], 0);
}

if($_POST['edit_element']) {
    unset($_POST['edit_element']);
    $api->update_element($_POST);
}

if($_POST['delete_element']) {
    $api->delete_element($_POST['id']);
}

if($_POST['update_block']) {
    $api->update_block($_POST['id'], $_POST['value']);
}

if($_POST['update_settings']) {
    unset($_POST['update_settings']);
    $api->update_settings($_POST);
}

if($_POST['update_admin_password'] && $api->is_admin_user()) {
    $api->update_user_password('admin', $_POST['update_admin_password']);
}

if($_POST['update_guest_password'] && $api->is_admin_user()) {
    $api->update_user_password('guest', $_POST['update_guest_password']);
}

@include __DIR__.'/custom/ajax.php';

if($_POST) {
    if($api->get_error()) {
        echo $api->get_error();
    }
    $cache->clean();
    exit();
}
?>
<? if(isset($_GET['data-admin-block'])):?>
    <a href="/_admin/ajax.php?form_edit_block=<?=$_GET['data-admin-block']?>"
       class="adm__button adm__edit_button fancybox fancybox.ajax" title="Редактировать"></a>
<? endif?>
<? if(isset($_GET['data-admin-element']) && $data = $api->get_element($_GET['data-admin-element'])):?>
    <a href="/_admin/ajax.php?form_add_element=<?=$_GET['data-admin-element']?>"
       class="adm__button adm__add_button fancybox fancybox.ajax" title="Добавить"></a>
    <a href="/_admin/ajax.php?form_edit_element=<?=$_GET['data-admin-element']?>"
       class="adm__button adm__edit_button fancybox fancybox.ajax" title="Редактировать"></a>
    <form class="adm__ajax_form" action="/_admin/ajax.php" method="POST">
        <input type="hidden" name="delete_element" value="1"/>
        <input type="hidden" name="id" value="<?=$_GET['data-admin-element']?>"/>
        <a href="javascript:void(0);" onclick="Admin.callback='deleteElement';Admin.confirmDelete=true;" class="adm__button adm__delete_button adm__submit" title="Удалить"></a>
    </form>
    <? if($data['visible']): ?>
        <form class="adm__ajax_form" action="/_admin/ajax.php" method="POST">
            <input type="hidden" name="hide_element" value="1"/>
            <input type="hidden" name="id" value="<?=$_GET['data-admin-element']?>"/>
            <a href="javascript:void(0);" class="adm__button adm__hide_button adm__submit" title="Удалить"></a>
        </form>
    <? else: ?>
        <form class="adm__ajax_form" action="/_admin/ajax.php" method="POST">
            <input type="hidden" name="show_element" value="1"/>
            <input type="hidden" name="id" value="<?=$_GET['data-admin-element']?>"/>
            <a href="javascript:void(0);" class="adm__button adm__show_button adm__submit" title="Удалить"></a>
        </form>
    <? endif; ?>
<? endif?>
<? if(isset($_GET['data-admin-elements']) && API::$elements[$_GET['data-admin-elements']]):?>
    <a href="/_admin/ajax.php?form_add_elements=<?=$_GET['data-admin-elements']?>"
       class="adm__button adm__add_button fancybox fancybox.ajax" title="Добавить"></a>
<? endif?>
<? if(isset($_GET['data-admin-page']) && $data = $api->get_page_by_url($_GET['data-admin-page'])):?>
    <?php if($data['fixed']):?>
        <a href="/_admin/ajax.php?form_edit_page=<?=$_GET['data-admin-page']?>"
           class="adm__button adm__edit_button fancybox fancybox.ajax" title="Редактировать"></a>
    <?php else:?>
        <a href="/_admin/ajax.php?form_add_page=<?=$_GET['data-admin-page']?>"
           class="adm__button adm__add_button fancybox fancybox.ajax" title="Добавить"></a>
        <a href="/_admin/ajax.php?form_edit_page=<?=$_GET['data-admin-page']?>"
           class="adm__button adm__edit_button fancybox fancybox.ajax" title="Редактировать"></a>
        <form class="adm__ajax_form" action="/_admin/ajax.php" method="POST">
            <input type="hidden" name="delete_page" value="1"/>
            <input type="hidden" name="node1" value="<?=$data['node1']?>"/>
            <input type="hidden" name="node2" value="<?=$data['node2']?>"/>
            <input type="hidden" name="node3" value="<?=$data['node3']?>"/>
            <a href="javascript:void(0);" onclick="/*Admin.callback='redirect';*/Admin.confirmDelete=true;" class="adm__button adm__delete_button adm__submit" title="Удалить"></a>
        </form>
    <?php endif?>
<? endif?>
<? if(isset($_GET['data-admin-pages'])):?>
    <a href="/_admin/ajax.php?form_add_pages=<?=$_GET['data-admin-pages']?>"
       class="adm__button adm__add_button fancybox fancybox.ajax" title="Добавить"></a>
<? endif?>
<? if(isset($_GET['form_add_element']) && $data = $api->get_element($_GET['form_add_element'])):?>
    <form id="adm__element" class="adm__form adm__narrow_form adm__ajax_form" action="/_admin/ajax.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="add_element" value="1"/>
        <input type="hidden" name="type" value="<?=$data['type']?>"/>
        <fieldset>
            <legend>Добавить элемент "<?=$data['type']?>"</legend>
            <? foreach(API::$elements[$data['type']] as $name => $type):?>
                <label>
                    <?=$name?>
                    <? if($type == 'varchar'):?>
                        <input type="text" name="data[<?=$name?>]" value=""/>
                    <? elseif($type == 'url'):?>
                        <input type="text" name="data[<?=$name?>]" value=""/>
                    <? elseif($type == 'int'):?>
                        <input type="text" name="data[<?=$name?>]" size="10" value=""/>
                    <? elseif($type == 'datetime'):?>
                        <input type="text" class="adm__datetimepicker" name="data[<?=$name?>]" value=""/>
                    <? elseif($type == 'text'):?>
                        <textarea name="data[<?=$name?>]"></textarea>
                    <? elseif($type == 'code'):?>
                        <textarea name="data[<?=$name?>]"></textarea>
                    <? elseif($type == 'html'):?>
                        <textarea class="mce__editor_small" id="data[<?=$name?>]" name="data[<?=$name?>]"></textarea>
                    <? elseif($type == 'bool'):?>
                        <input type="checkbox" name="data[<?=$name?>]" value="1" checked/>
                    <? elseif($type == 'image'):?>
                        <input type="hidden" name="data[<?=$name?>]" value=""/>
                        <input type="file" name="<?=$name?>"/>
                    <? elseif($type == 'images'):?>
                        <input type="hidden" name="data[<?=$name?>]" value=""/>
                        <input type="file" name="<?=$name?>[]" multiple/>
                    <? elseif($type == 'file'):?>
                        <input type="hidden" name="data[<?=$name?>]" value=""/>
                        <input type="file" name="<?=$name?>"/>
                    <? elseif($type == 'files'):?>
                        <input type="hidden" name="data[<?=$name?>]" value=""/>
                        <input type="file" name="<?=$name?>[]" multiple/>
                    <? endif?>
                </label>
            <? endforeach?>
            <label><input type="checkbox" name="visible" value="1" checked/> видно</label>
        </fieldset>
        <button type="submit">Сохранить</button>
        <button type="submit" onclick="Admin.callback='reset';">Сохранить и добавить еще</button>
    </form>
<? endif?>
<? if(isset($_GET['form_add_elements']) && API::$elements[$_GET['form_add_elements']]):?>
    <form id="adm__element" class="adm__form adm__narrow_form adm__ajax_form" action="/_admin/ajax.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="add_element" value="1"/>
        <input type="hidden" name="type" value="<?=$_GET['form_add_elements']?>"/>
        <fieldset>
            <legend>Добавить элемент "<?=$_GET['form_add_elements']?>"</legend>
            <? foreach(API::$elements[$_GET['form_add_elements']] as $name => $type):?>
                <label>
                    <?=$name?>
                    <? if($type == 'varchar'):?>
                        <input type="text" name="data[<?=$name?>]" value=""/>
                    <? elseif($type == 'url'):?>
                        <input type="text" name="data[<?=$name?>]" value=""/>
                    <? elseif($type == 'int'):?>
                        <input type="text" name="data[<?=$name?>]" size="10" value=""/>
                    <? elseif($type == 'datetime'):?>
                        <input type="text" class="adm__datetimepicker" name="data[<?=$name?>]" value=""/>
                    <? elseif($type == 'text'):?>
                        <textarea name="data[<?=$name?>]"></textarea>
                    <? elseif($type == 'code'):?>
                        <textarea name="data[<?=$name?>]"></textarea>
                    <? elseif($type == 'html'):?>
                        <textarea class="mce__editor_small" name="data[<?=$name?>]"></textarea>
                    <? elseif($type == 'bool'):?>
                        <input type="checkbox" name="data[<?=$name?>]" value="1" checked/>
                    <? elseif($type == 'image'):?>
                        <input type="hidden" name="data[<?=$name?>]" value=""/>
                        <input type="file" name="<?=$name?>"/>
                    <? elseif($type == 'images'):?>
                        <input type="hidden" name="data[<?=$name?>][]" value=""/>
                        <input type="file" name="<?=$name?>[]" multiple/>
                    <? elseif($type == 'file'):?>
                        <input type="hidden" name="data[<?=$name?>]" value=""/>
                        <input type="file" name="<?=$name?>"/>
                    <? elseif($type == 'files'):?>
                        <input type="hidden" name="data[<?=$name?>][]" value=""/>
                        <input type="file" name="<?=$name?>[]" multiple/>
                    <? endif ?>
                </label>
            <? endforeach?>
            <label><input type="checkbox" name="visible" value="1" checked/> видно</label>
        </fieldset>
        <button type="submit">Сохранить</button>
        <button type="submit" onclick="Admin.callback='reset';">Сохранить и добавить еще</button>
    </form>
<? endif?>
<? if(isset($_GET['form_edit_element']) && $data = $api->get_element($_GET['form_edit_element'])):?>
    <form id="adm__element" class="adm__form adm__narrow_form adm__ajax_form" action="/_admin/ajax.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="edit_element" value="1"/>
        <input type="hidden" name="id" value="<?=$data['id']?>"/>
        <input type="hidden" name="type" value="<?=$data['type']?>"/>
        <fieldset>
            <legend>Редактировать элемент "<?=$data['type']?>"</legend>
            <? foreach(API::$elements[$data['type']] as $name => $type):?>
                <label>
                    <?=$name?>
                    <? if($type == 'varchar'):?>
                        <input type="text" name="data[<?=$name?>]" value="<?=$data['data'][$name]?>"/>
                    <? elseif($type == 'url'):?>
                        <input type="text" name="data[<?=$name?>]" value="<?=$data['data'][$name]?>"/>
                    <? elseif($type == 'int'):?>
                        <input type="text" name="data[<?=$name?>]" size="10" value="<?=$data['data'][$name]?>"/>
                    <? elseif($type == 'datetime'):?>
                        <input type="text" class="adm__datetimepicker" name="data[<?=$name?>]" value="<?=$data['data'][$name]?>"/>
                    <? elseif($type == 'text'):?>
                        <textarea name="data[<?=$name?>]"><?=$data['data'][$name]?></textarea>
                    <? elseif($type == 'code'):?>
                        <textarea name="data[<?=$name?>]"><?=$data['data'][$name]?></textarea>
                    <? elseif($type == 'html'):?>
                        <textarea class="mce__editor_small" name="data[<?=$name?>]"><?=$data['data'][$name]?></textarea>
                    <? elseif($type == 'bool'):?>
                        <input type="checkbox" name="data[<?=$name?>]" value="1" <?=$data['data'][$name] ? 'checked' : ''?>/>
                    <? elseif($type == 'image'):?>
                        <input type="text" name="data[<?=$name?>]" value="<?=$data['data'][$name]?>"/>
                        <input type="file" name="<?=$name?>"/>
                    <? elseif($type == 'images'):?>
                        <input type="hidden" name="data[<?=$name?>][]" value=""/>
                        <input type="file" name="<?=$name?>[]" multiple/>
                        <? foreach($data['data'][$name] as $key=>$value):?>
                            <input type="text" name="data[<?=$name?>][<?=$key?>]" value="<?=$value?>"/>
                        <? endforeach?>
                    <? elseif($type == 'file'):?>
                        <input type="text" name="data[<?=$name?>]" value="<?=$data['data'][$name]?>"/>
                        <input type="file" name="<?=$name?>"/>
                    <? elseif($type == 'files'):?>
                        <input type="hidden" name="data[<?=$name?>][]" value=""/>
                        <input type="file" name="<?=$name?>[]" multiple/>
                        <? foreach($data['data'][$name] as $key=>$value):?>
                            <input type="text" name="data[<?=$name?>][<?=$key?>]" value="<?=$value?>"/>
                        <? endforeach?>
                    <? endif?>
                </label>
            <? endforeach?>
            <label><input type="checkbox" name="visible" value="1" <?if($data['visible']):?>checked<?endif?>/> видно</label>
        </fieldset>
        <button type="submit">Сохранить</button>
    </form>
<? endif?>
<? if(isset($_GET['form_add_page']) && $data = $api->get_page_by_url($_GET['form_add_page'])):?>
    <form id="adm__page" class="adm__form adm__wide_form adm__ajax_form" action="/_admin/ajax.php" method="POST" enctype="multipart/form-data">
        <h3>Добавить страницу "<?=$data['type']?>"</h3>
        <input type="hidden" name="add_page" value="1"/>
        <input type="hidden" name="type" value="<?=$data['type']?>"/>
        <fieldset>
            <legend>Основное</legend>
            <label><input type="checkbox" name="visible" value="1" checked/> видимость</label>
            <fieldset>
                <legend>Адрес</legend>
                <div class="adm__inline_page_inputs">
                    <? if(API::$pages[API::$pages[$data['type']]['parent']]['parent']): $nodes = $api->get_parent_page_nodes($data['type']); ?>
                    <select name="node_text1" onchange="Admin.refreshSelects(this);" required>
                        <? foreach($nodes as $node1): ?>
                            <option value="<?=$node1['name']?>" <?=($data['node1']==$node1['url'])?'selected':''?> ><?=$node1['name']?></option>
                        <? endforeach ?>
                    </select>
                    /
                    <? foreach ($nodes as $node1): ?>
                    <? if ($data['node1'] == $node1['url']): ?>
                    <select name="node_text2" data-name="<?= $node1['name'] ?>" required>
                        <? else: ?>
                        <select name="" data-name="<?= $node1['name'] ?>" style="display: none" required>
                            <? endif; ?>
                            <? foreach($node1['children'] as $node2): ?>
                                <? if($data['node1'] == $node1['url'] && $data['node2'] == $node2['url']): ?>
                                    <option value="<?=$node2['name']?>" selected><?=$node2['name']?></option>
                                <? else: ?>
                                    <option value="<?=$node2['name']?>"><?=$node2['name']?></option>
                                <? endif; ?>
                            <? endforeach; ?>
                        </select>
                        <? endforeach; ?>
                        /
                        <input type="text" name="node_text3" value="" required/>
                        <? elseif(API::$pages[$data['type']]['parent']): $nodes = $api->get_parent_page_nodes($data['type']); ?>
                            <select name="node_text1" required>
                                <? foreach($nodes as $node1): ?>
                                    <option value="<?=$node1['name']?>" <?=($data['node1']==$node1['url'])?'selected':''?> ><?=$node1['name']?></option>
                                <? endforeach; ?>
                            </select>
                            /
                            <input type="text" name="node_text2" value="" required/>
                        <? else: ?>
                            <input type="text" name="node_text1" value="" required/>
                        <? endif; ?>
                </div>
            </fieldset>
            <label>Заголовок<input type="text" name="title" value="" required/></label>
            <label>Описание<input type="text" name="description" value="" required/></label>
            <label>Ключевые слова<input type="text" name="keywords" value=""/></label>
            <label>Текст
                <textarea class="mce__editor" name="text"></textarea>
            </label>
            <label>Дата публикации
                <input type="text" class="adm__datetimepicker" name="date_created" value=""/>
            </label>
            <label>Дата последнего редактирования
                <input type="text" class="adm__datetimepicker" name="date_lastmod" value=""/>
            </label>
        </fieldset>
		<? if(API::$pages[$data['type']]['elements']):?>
        <fieldset>
            <legend>Опции</legend>
			<? foreach(API::$pages[$data['type']]['elements'] as $element_name): ?>
				<label><?=$element_name?>
				<select name="elements[<?=$element_name?>][]" multiple>
					<? foreach($api->get_elements_data($element_name) as $id=>$edata): ?>
						<option value="<?=$id?>"><?=array_shift($edata)?></option>
					<? endforeach; ?>
				</select>
				</label>
			<? endforeach; ?>
        </fieldset>
		<? endif; ?>
        <? if(API::$pages[$data['type']]['custom']):?>
            <fieldset>
                <legend>Дополнительно</legend>
                <? foreach(API::$pages[$data['type']]['custom'] as $name=>$type):?>
                    <? if(is_array($type)): ?>
                        <fieldset class="adm__sub_element_wrapper">
                            <fieldset class="adm__sub_element">
                                <legend><?=$name?></legend>
                                <? foreach(API::$pages[$data['type']]['custom'][$name] as $name2=>$type2):?>
                                    <label>
                                        <?=$name2?>
                                        <? if($type2 == 'varchar'):?>
                                            <input type="text" name="custom[<?=$name?>][0][<?=$name2?>]" value=""/>
                                        <? elseif($type2 == 'url'):?>
                                            <input type="text" name="custom[<?=$name?>][0][<?=$name2?>]" value=""/>
                                        <? elseif($type2 == 'int'):?>
                                            <input type="text" size="10" name="custom[<?=$name?>][0][<?=$name2?>]" value=""/>
                                        <? elseif($type2 == 'datetime'):?>
                                            <input type="text" class="adm__datetimepicker" name="custom[<?=$name?>][0][<?=$name2?>]" value=""/>
                                        <? elseif($type2 == 'text'):?>
                                            <textarea name="custom[<?=$name?>][0][<?=$name2?>]"></textarea>
                                        <? elseif($type2 == 'code'):?>
                                            <textarea name="custom[<?=$name?>][0][<?=$name2?>]"></textarea>
                                        <? elseif($type2 == 'html'):?>
                                            <textarea class="mce__editor_small" id="custom[<?=$name?>][0][<?=$name2?>]" name="custom[<?=$name?>][0][<?=$name2?>]"></textarea>
                                        <? elseif($type2 == 'bool'):?>
                                            <input type="checkbox" name="custom[<?=$name?>][0][<?=$name2?>]" value="1"/>
                                        <? endif?>
                                    </label>
                                <? endforeach?>
                                <p style="text-align: right">
                                    <a href="javascript:void(0);" onclick="Admin.addSubElement(this);">Добавить</a>
                                    <a href="javascript:void(0);" onclick="Admin.removeSubElement(this);">Удалить</a>
                                </p>
                            </fieldset>
                        </fieldset>
                    <? else: ?>
                        <label>
                            <?=$name?>
                            <? if($type == 'varchar'):?>
                                <input type="text" name="custom[<?=$name?>]" value=""/>
                            <? elseif($type == 'url'):?>
                                <input type="text" name="custom[<?=$name?>]" value=""/>
                            <? elseif($type == 'int'):?>
                                <input type="text" size="10" name="custom[<?=$name?>]" value=""/>
                            <? elseif($type == 'datetime'):?>
                                <input type="text" class="adm__datetimepicker" name="custom[<?=$name?>]" value=""/>
                            <? elseif($type == 'text'):?>
                                <textarea name="custom[<?=$name?>]"></textarea>
                            <? elseif($type == 'code'):?>
                                <textarea name="custom[<?=$name?>]"></textarea>
                            <? elseif($type == 'html'):?>
                                <textarea class="mce__editor_small" name="custom[<?=$name?>]"></textarea>
                            <? elseif($type == 'bool'):?>
                                <input type="checkbox" name="custom[<?=$name?>]" value="1"/>
                            <? elseif($type == 'image'):?>
                                <input type="hidden" name="custom[<?=$name?>]" value=""/>
                                <input type="file" name="<?=$name?>"/>
                            <? elseif($type == 'images'):?>
                                <input type="hidden" name="custom[<?=$name?>][]" value=""/>
                                <input type="file" name="<?=$name?>[]" multiple/>
                            <? elseif($type == 'file'):?>
                                <input type="hidden" name="custom[<?=$name?>]" value=""/>
                                <input type="file" name="<?=$name?>"/>
                            <? elseif($type == 'files'):?>
                                <input type="hidden" name="custom[<?=$name?>][]" value=""/>
                                <input type="file" name="<?=$name?>[]" multiple/>
                            <? endif?>
                        </label>
                    <? endif; ?>
                <? endforeach?>
            </fieldset>
        <? endif?>
        <button type="submit">Сохранить</button>
        <button type="submit" onclick="Admin.callback='reset';">Сохранить и добавить еще</button>
    </form>
<? endif?>
<? if(isset($_GET['form_add_pages']) && API::$pages[$_GET['form_add_pages']]):?>
    <form id="adm__page" class="adm__form adm__wide_form adm__ajax_form" action="/_admin/ajax.php" method="POST" enctype="multipart/form-data">
        <h3>Добавить страницу "<?=$_GET['form_add_pages']?>"</h3>
        <input type="hidden" name="add_page" value="1"/>
        <input type="hidden" name="type" value="<?=$_GET['form_add_pages']?>"/>
        <fieldset>
            <legend>Основное</legend>
            <label><input type="checkbox" name="visible" value="1" checked/> видимость</label>
            <fieldset>
                <legend>Адрес</legend>
                <div class="adm__inline_page_inputs">
                    <? if(API::$pages[API::$pages[$_GET['form_add_pages']]['parent']]['parent']): ?>
                    <? $nodes = $api->get_parent_page_nodes($_GET['form_add_pages']); ?>
                    <select name="node_text1" onchange="Admin.refreshSelects(this);" required>
                        <? foreach($nodes as $node1): ?>
                            <option value="<?=$node1['name']?>"><?=$node1['name']?></option>
                        <? endforeach ?>
                    </select>
                    /
                    <? $first=true; ?>
                    <?foreach($nodes as $node1): ?>
                    <? if($first): ?>
                    <select name="node_text2" data-name="<?=$node1['name']?>" required>
                        <?else:?>
                        <select name="" data-name="<?=$node1['name']?>" style="display: none" required>
                            <? endif; ?>
                            <? foreach($node1['children'] as $node2): ?>
                                <option value="<?=$node2['name']?>"><?=$node2['name']?></option>
                            <? endforeach ?>
                        </select>
                        <? $first=false; ?>
                        <? endforeach; ?>
                        /
                        <input type="text" name="node_text3" required>
                        <? elseif(API::$pages[$_GET['form_add_pages']]['parent']): ?>
                            <? $nodes = $api->get_parent_page_nodes($_GET['form_add_pages']); ?>
                            <select name="node_text1" required>
                                <? foreach($nodes as $node): ?>
                                    <option value="<?=$node['name']?>"><?=$node['name']?></option>
                                <? endforeach; ?>
                            </select>
                            /
                            <input type="text" name="node_text2" required>
                        <? else: ?>
                            <input type="text" name="node_text1" required>
                        <? endif; ?>
                </div>
            </fieldset>
            <label>Заголовок<input type="text" name="title" value="" required/></label>
            <label>Описание<input type="text" name="description" value="" required/></label>
            <label>Ключевые слова<input type="text" name="keywords" value=""/></label>
            <label>Текст<textarea class="mce__editor" name="text"></textarea></label>
            <label>Дата публикации
                <input type="text" class="adm__datetimepicker" name="date_created" value=""/>
            </label>
            <label>Дата последнего редактирования
                <input type="text" class="adm__datetimepicker" name="date_lastmod" value=""/>
            </label>
        </fieldset>
		<? if(API::$pages[$data['type']]['elements']):?>
        <fieldset>
            <legend>Опции</legend>
			<? foreach(API::$pages[$data['type']]['elements'] as $element_name): ?>
				<label><?=$element_name?>
					<select name="elements[<?=$element_name?>][]" multiple>
						<? foreach($api->get_elements_data($element_name) as $id=>$edata): ?>
							<option value="<?=$id?>"><?=array_shift($edata)?></option>
						<? endforeach; ?>
					</select>
				</label>
			<? endforeach; ?>
        </fieldset>
		<? endif; ?>
        <? if(API::$pages[$_GET['form_add_pages']]['custom']):?>
            <fieldset>
                <legend>Дополнительно</legend>
                <? foreach(API::$pages[$_GET['form_add_pages']]['custom'] as $name=>$type):?>
                    <?if(is_array($type)):?>
                        <fieldset class="adm__sub_element_wrapper">
                            <fieldset class="adm__sub_element">
                                <legend><?=$name?></legend>
                                <? foreach(API::$pages[$_GET['form_add_pages']]['custom'][$name] as $name2=>$type2):?>
                                    <label>
                                        <?=$name2?>
                                        <? if($type2 == 'varchar'):?>
                                            <input type="text" name="custom[<?=$name?>][0][<?=$name2?>]" value=""/>
                                        <? elseif($type2 == 'url'):?>
                                            <input type="text" name="custom[<?=$name?>][0][<?=$name2?>]" value=""/>
                                        <? elseif($type2 == 'int'):?>
                                            <input type="text" size="10" name="custom[<?=$name?>][0][<?=$name2?>]" value=""/>
                                        <? elseif($type2 == 'datetime'):?>
                                            <input type="text" class="adm__datetimepicker" name="custom[<?=$name?>][0][<?=$name2?>]" value=""/>
                                        <? elseif($type2 == 'text'):?>
                                            <textarea name="custom[<?=$name?>][0][<?=$name2?>]"></textarea>
                                        <? elseif($type2 == 'code'):?>
                                            <textarea name="custom[<?=$name?>][0][<?=$name2?>]"></textarea>
                                        <? elseif($type2 == 'html'):?>
                                            <textarea class="mce__editor_small" id="custom[<?=$name?>][0][<?=$name2?>]" name="custom[<?=$name?>][0][<?=$name2?>]"></textarea>
                                        <? elseif($type2 == 'bool'):?>
                                            <input type="checkbox" name="custom[<?=$name?>][0][<?=$name2?>]" value="1"/>
                                        <? endif?>
                                    </label>
                                <? endforeach?>
                                <p style="text-align: right">
                                    <a href="javascript:void(0);" onclick="Admin.addSubElement(this);">Добавить</a>
                                    <a href="javascript:void(0);" onclick="Admin.removeSubElement(this);">Удалить</a>
                                </p>
                            </fieldset>
                        </fieldset>
                    <?else:?>
                        <label>
                            <?=$name?>
                            <? if($type == 'varchar'):?>
                                <input type="text" name="custom[<?=$name?>]" value=""/>
                            <? elseif($type == 'url'):?>
                                <input type="text" name="custom[<?=$name?>]" value=""/>
                            <? elseif($type == 'int'):?>
                                <input type="text" size="10" name="custom[<?=$name?>]" value=""/>
                            <? elseif($type == 'datetime'):?>
                                <input type="text" class="adm__datetimepicker" name="custom[<?=$name?>]" value=""/>
                            <? elseif($type == 'text'):?>
                                <textarea name="custom[<?=$name?>]"></textarea>
                            <? elseif($type == 'code'):?>
                                <textarea name="custom[<?=$name?>]"></textarea>
                            <? elseif($type == 'html'):?>
                                <textarea class="mce__editor_small" name="custom[<?=$name?>]"></textarea>
                            <? elseif($type == 'bool'):?>
                                <input type="checkbox" name="custom[<?=$name?>]" value="1"/>
                            <? elseif($type == 'image'):?>
                                <input type="hidden" name="custom[<?=$name?>]" value=""/>
                                <input type="file" name="<?=$name?>"/>
                            <? elseif($type == 'images'):?>
                                <input type="hidden" name="custom[<?=$name?>][]" value=""/>
                                <input type="file" name="<?=$name?>[]" multiple/>
                            <? elseif($type == 'file'):?>
                                <input type="hidden" name="custom[<?=$name?>]" value=""/>
                                <input type="file" name="<?=$name?>"/>
                            <? elseif($type == 'files'):?>
                                <input type="hidden" name="custom[<?=$name?>][]" value=""/>
                                <input type="file" name="<?=$name?>[]" multiple/>
                            <? endif?>
                        </label>
                    <?endif;?>
                <? endforeach?>
            </fieldset>
        <? endif?>
        <button type="submit">Сохранить</button>
        <button type="submit" onclick="Admin.callback='reset';">Сохранить и добавить еще</button>
    </form>
<? endif?>
<? if(isset($_GET['form_edit_page']) && $data = $api->get_page_by_url($_GET['form_edit_page'])):?>
    <form id="adm__page" class="adm__form adm__wide_form adm__ajax_form" action="/_admin/ajax.php" method="POST" enctype="multipart/form-data">
        <h3>Редактировать страницу "<?=$data['type']?>"</h3>
        <input type="hidden" name="edit_page" value="1"/>
        <input type="hidden" name="old_node1" value="<?=$data['node1']?>"/>
        <input type="hidden" name="old_node2" value="<?=$data['node2']?>"/>
        <input type="hidden" name="old_node3" value="<?=$data['node3']?>"/>
        <input type="hidden" name="type" value="<?=$data['type']?>"/>
        <fieldset>
            <legend>Основное</legend>
			<label><input type="checkbox" name="visible" value="1" <?=($data['visible'] ? 'checked' : '')?>/> видимость</label>

            <fieldset>
                <legend>Адрес</legend>
                <div class="adm__inline_page_inputs">
                    <? if(API::$pages[API::$pages[$data['type']]['parent']]['parent']): $nodes = $api->get_parent_page_nodes($data['type']); ?>
                    <select name="node_text1" onchange="Admin.refreshSelects(this);" required>
                        <? foreach($nodes as $node1): ?>
                            <option value="<?=$node1['name']?>" <?=($data['node1']==$node1['url'])?'selected':''?> ><?=$node1['name']?></option>
                        <? endforeach ?>
                    </select>
                    /
                    <? foreach ($nodes as $node1): ?>
                    <? if ($data['node1'] == $node1['url']): ?>
                    <select name="node_text2" data-name="<?= $node1['name'] ?>" required>
                        <? else: ?>
                        <select name="" data-name="<?= $node1['name'] ?>" style="display: none" required>
                            <? endif; ?>
                            <? foreach($node1['children'] as $node2): ?>
                                <? if($data['node1'] == $node1['url'] && $data['node2'] == $node2['url']): ?>
                                    <option value="<?= $node2['name'] ?>" selected><?= $node2['name'] ?></option>
                                <? else: ?>
                                    <option value="<?= $node2['name'] ?>"><?= $node2['name'] ?></option>
                                <? endif; ?>
                            <? endforeach; ?>
                        </select>
                        <? endforeach; ?>
                        /
                        <input type="text" name="node_text3" value="<?=$data['node_text3']?>" required/>
                        <? elseif(API::$pages[$data['type']]['parent']): $nodes = $api->get_parent_page_nodes($data['type']); ?>
                            <select name="node_text1" required>
                                <? foreach($nodes as $node1): ?>
                                    <option value="<?=$node1['name']?>" <?=($data['node1']==$node1['url'])?'selected':''?> ><?=$node1['name']?></option>
                                <? endforeach; ?>
                            </select>
                            /
                            <input type="text" name="node_text2" value="<?=$data['node_text2']?>" required/>
                        <? else: ?>
                            <input type="text" name="node_text1" value="<?=$data['node_text1']?>" required/>
                        <? endif; ?>
                </div>
            </fieldset>

            <label>Заголовок<input type="text" name="title" value="<?=$data['title']?>" required/></label>
            <label>Описание<input type="text" name="description" value="<?=$data['description']?>" required/></label>
            <label>Ключевые слова<input type="text" name="keywords" value="<?=$data['keywords']?>"/></label>
            <label>Текст<textarea class="mce__editor" name="text"><?=$data['text']?></textarea></label>
            <label>Дата публикации
                <input type="text" class="adm__datetimepicker" name="date_created" value="<?=$data['date_created']?>"/>
            </label>
            <label>Дата последнего редактирования
                <input type="text" class="adm__datetimepicker" name="date_lastmod" value="<?=$data['date_lastmod']?>"/>
            </label>
        </fieldset>
		<? if(API::$pages[$data['type']]['elements']):?>
        <fieldset>
            <legend>Опции</legend>
			<? foreach(API::$pages[$data['type']]['elements'] as $element_name): ?>
				<label><?=$element_name?>
					<select name="elements[<?=$element_name?>][]" multiple>
						<? foreach($api->get_elements_data($element_name) as $id=>$edata): ?>
							<option value="<?=$id?>" <?= in_array($id, $data['elements'][$element_name]) ? "selected":"" ?> ><?=array_shift($edata)?></option>
						<? endforeach; ?>
					</select>
				</label>
			<? endforeach; ?>
        </fieldset>
		<? endif; ?>
        <? if(API::$pages[$data['type']]['custom']):?>
            <fieldset>
                <legend>Дополнительно</legend>
                <? foreach(API::$pages[$data['type']]['custom'] as $name=>$type):?>
                    <?if(is_array($type)):?>
                        <fieldset class="adm__sub_element_wrapper">
                            <? if(!$data['custom'][$name]) $data['custom'][$name][]=array(); ?>
                            <? foreach($data['custom'][$name] as $key=>$val): ?>
                                <fieldset class="adm__sub_element">
                                    <legend><?=$name?></legend>
                                    <? foreach(API::$pages[$data['type']]['custom'][$name] as $name2=>$type2):?>
                                        <label>
                                            <?=$name2?>
                                            <? if($type2 == 'varchar'):?>
                                                <input type="text" name="custom[<?=$name?>][<?=$key?>][<?=$name2?>]" value="<?=$data['custom'][$name][$key][$name2]?>"/>
                                            <? elseif($type2 == 'url'):?>
                                                <input type="text" name="custom[<?=$name?>][<?=$key?>][<?=$name2?>]" value="<?=$data['custom'][$name][$key][$name2]?>"/>
                                            <? elseif($type2 == 'int'):?>
                                                <input type="text" size="10" name="custom[<?=$name?>][<?=$key?>][<?=$name2?>]" value="<?=$data['custom'][$name][$key][$name2]?>"/>
                                            <? elseif($type2 == 'datetime'):?>
                                                <input type="text" class="adm__datetimepicker" name="custom[<?=$name?>][<?=$key?>][<?=$name2?>]" value="<?=$data['custom'][$name][$key][$name2]?>"/>
                                            <? elseif($type2 == 'text'):?>
                                                <textarea name="custom[<?=$name?>][<?=$key?>][<?=$name2?>]"><?=$data['custom'][$name][$key][$name2]?></textarea>
                                            <? elseif($type2 == 'code'):?>
                                                <textarea name="custom[<?=$name?>][<?=$key?>][<?=$name2?>]"><?=$data['custom'][$name][$key][$name2]?></textarea>
                                            <? elseif($type2 == 'html'):?>
                                                <textarea class="mce__editor_small" id="custom[<?=$name?>][<?=$key?>][<?=$name2?>]" name="custom[<?=$name?>][<?=$key?>][<?=$name2?>]"><?=$data['custom'][$name][$key][$name2]?></textarea>
                                            <? elseif($type2 == 'bool'):?>
                                                <input type="checkbox" name="custom[<?=$name?>][<?=$key?>][<?=$name2?>]" value="1" <?=$data['custom'][$name][$key][$name2]?'checked':''?>/>
                                            <? endif?>
                                        </label>
                                    <? endforeach;?>

                                    <p style="text-align: right">
                                        <a href="javascript:void(0);" onclick="Admin.addSubElement(this);">Добавить</a>
                                        <a href="javascript:void(0);" onclick="Admin.removeSubElement(this);">Удалить</a>
                                    </p>
                                </fieldset>
                            <? endforeach;?>
                        </fieldset>
                    <?else:?>
                        <label>
                            <?=$name?>
                            <? if($type == 'varchar'):?>
                                <input type="text" name="custom[<?=$name?>]" value="<?=$data['custom'][$name]?>"/>
                            <? elseif($type == 'url'):?>
                                <input type="text" name="custom[<?=$name?>]" value="<?=$data['custom'][$name]?>"/>
                            <? elseif($type == 'int'):?>
                                <input type="text" size="10" name="custom[<?=$name?>]" value="<?=$data['custom'][$name]?>"/>
                            <? elseif($type == 'datetime'):?>
                                <input type="text" class="adm__datetimepicker" name="custom[<?=$name?>]" value="<?=$data['custom'][$name]?>"/>
                            <? elseif($type == 'text'):?>
                                <textarea name="custom[<?=$name?>]"><?=$data['custom'][$name]?></textarea>
                            <? elseif($type == 'code'):?>
                                <textarea name="custom[<?=$name?>]"><?=$data['custom'][$name]?></textarea>
                            <? elseif($type == 'html'):?>
                                <textarea class="mce__editor_small" id="custom[<?=$name?>]" name="custom[<?=$name?>]"><?=$data['custom'][$name]?></textarea>
                            <? elseif($type == 'bool'):?>
                                <input type="checkbox" name="custom[<?=$name?>]" value="1" <?=$data['custom'][$name]?'checked':''?>/>
                            <? elseif($type == 'image'):?>
                                <input type="text" name="custom[<?=$name?>]" value="<?=$data['custom'][$name]?>"/>
                                <input type="file" name="<?=$name?>"/>
                            <? elseif($type == 'images'):?>
                                <input type="hidden" name="custom[<?=$name?>][]" value=""/>
                                <input type="file" name="<?=$name?>[]" multiple/>
                                <? foreach($data['custom'][$name] as $key=>$value):?>
                                    <input type="text" name="custom[<?=$name?>][<?=$key?>]" value="<?=$value?>"/>
                                <? endforeach?>
                            <? elseif($type == 'file'):?>
                                <input type="text" name="custom[<?=$name?>]" value="<?=$data['custom'][$name]?>"/>
                                <input type="file" name="<?=$name?>"/>
                            <? elseif($type == 'files'):?>
                                <input type="hidden" name="custom[<?=$name?>][]" value=""/>
                                <input type="file" name="<?=$name?>[]" multiple/>
                                <? foreach($data['custom'][$name] as $key=>$value):?>
                                    <input type="text" name="custom[<?=$name?>][<?=$key?>]" value="<?=$value?>"/>
                                <? endforeach?>
                            <? endif?>
                        </label>
                    <? endif?>
                <? endforeach?>
            </fieldset>
        <? endif?>
        <button type="submit">Сохранить</button>
    </form>
<? endif?>
<? if(isset($_GET['form_edit_block']) && $data = $api->get_block($_GET['form_edit_block'])):?>
    <form id="adm__block" class="adm__form adm__wide_form adm__ajax_form" action="/_admin/ajax.php" method="POST">
        <input type="hidden" name="update_block" value="1"/>
        <input type="hidden" name="id" value="<?=$data['id']?>"/>
        <fieldset>
            <legend>Редактировать текстовый блок</legend>
            <textarea class="mce__editor" name="value"><?=$data['value']?></textarea>
        </fieldset>
        <button type="submit">Сохранить</button>
    </form>
<? endif?>
<? if($_GET['form_settings'] && $data = $api->get_settings()):?>
    <form action="/_admin/ajax.php" method="POST" id="adm__settings" class="adm__form adm__wide_form adm__ajax_form" enctype="multipart/form-data">
        <input type="hidden" name="update_settings" value="1"/>
        <fieldset>
            <legend>Настройки сайта</legend>
            <? if($api->is_admin_user()): ?>
                <div class="adm__tree_column">
                    <label>
                        Почта администратора <input type="text" name="admin-email" value="<?= $data['admin-email'] ?>"/>
                    </label>
                </div>
                <div class="adm__tree_column">
                    <label>
                        admin пароль <input type="password" name="update_admin_password" autocomplete="off"/>
                    </label>
                </div>
                <div class="adm__tree_column">
                    <label>
                        guest пароль <input type="password" name="update_guest_password" autocomplete="off"/>
                    </label>
                </div>

            <? endif; ?>
            <label>
                <img src="/<?=API::IMAGES_DOWNLOADS_DIR?>/favicon.ico?<?=$api->version()?>" style="width:16px;">
                Favicon
                <input type="file" name="favicon">
            </label>
            <label>
                <img src="/<?=API::IMAGES_DOWNLOADS_DIR?>/logo.png?<?=$api->version()?>" style="width:50px;">
                Логотип
                <input type="file" name="logo">
            </label>
            <label>Пользовательский код/теги между &lt;head&gt;...&lt;/head&gt;
                <textarea name="head-code"><?=$data['head-code']?></textarea>
            </label>
            <label>Пользовательский код/теги между &lt;body&gt;...&lt;/body&gt;
                <textarea name="body-code"><?=$data['body-code']?></textarea>
            </label>
        </fieldset>
        <button type="submit">Сохранить</button>
    </form>
<? endif?>