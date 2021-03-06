<?php
/*---------------------------------------------------------------------------
 * @Project: Alto CMS
 * @Project URI: http://altocms.com
 * @Description: Advanced Community Engine
 * @Copyright: Alto CMS Team
 * @License: GNU GPL v2 & MIT
 *----------------------------------------------------------------------------*/

/**
 * HookSnippet.class.php
 * Файл хука сниппетов
 *
 * @author      Андрей Воронов <andreyv@gladcode.ru>
 * @version     0.0.1.1 от 21.12.2014 21:45
 * @since       1.1
 */
class HookSnippet extends Hook {

    /**
     * Регистрация хуков
     */
    public function RegisterHook() {

        // Хук обработки шаблонного сниппета
        $this->AddHook('snippet_template_type', 'SnippetTemplateType');

        // Хук вывода имени пользователя
        $this->AddHook('snippet_user', 'SnippetUser');
        // Хук сниппета фотосета
        $this->AddHook('snippet_photoset', 'SnippetPhotoset');
    }

    /**
     * Хук обработки шаблонного сниппета
     *
     * @param $aData
     * @return bool|string
     */
    public function SnippetTemplateType($aData) {

        // Для шаблонного сниппета обязательно должен быть параметр имени
        if (!isset($aData['params']['snippet_name'])) {
            return FALSE;
        }

        // Получим html-код сниппета
        $aVars = array('aParams' => isset($aData['params']) ? $aData['params'] : array());

        $aData['result'] = trim(E::ModuleViewer()->GetLocalViewer()->Fetch("tpls/snippets/snippet.{$aData['params']['snippet_name']}.tpl", $aVars));

        return $aData['result'];
    }

    /**
     * Метод осуществляет обработку сниппета вставки имени
     * пользователя.
     *
     * @param $aData
     * @return bool|string
     */
    public function SnippetUser($aData) {

        // Получим параметры, собственно, он тут единственный - это
        // имя пользователя которое и добавляем в редактор
        if (!($sUserLogin = isset($aData['params']['login']) ? $aData['params']['login'] : FALSE)) {
            return FALSE;
        }

        // Если пользователь найден, то вернём ссылку на него
        if (is_string($sUserLogin) && ($oUser = E::ModuleUser()->GetUserByLogin($sUserLogin))) {
            // Получим html-код сниппета
            $aVars = array('oUser' => $oUser);

            $aData['result'] = trim(E::ModuleViewer()->Fetch('tpls/snippets/snippet.user.tpl', $aVars));

            return $aData['result'];
        }

        // Иначе, затрём сниппет
        return FALSE;

    }

    /**
     * Возвращает html-код фотосета
     *
     * @param $aData
     * @return bool
     */
    public function SnippetPhotoset($aData) {

        // Попытаемся определить откуда вызывается сниппет фотосета
        // поскольку нужно точно определить целевой объект и его ид

        // Редактируется топик.
        // Получим его ид. и по нему поднимем необходимый фотосет
        $aAdminMatches = array();
        if (preg_match('~content\/edit\/(\d+)\/~', R::GetControllerPath(), $aMatches)
            || preg_match('~admin\/content-pages\/edit\/(\d+)\/~', R::GetControllerPath(), $aAdminMatches)
        ) {

            // Найдем топик, из которого будем брать фотосет
            $iTopicId = (int)isset($aData['params']['topic']) ? $aData['params']['topic'] : ($aAdminMatches ? FALSE : $aMatches[1]);


            // Странно, но топик не нашли - завернём сниппет
            if (!($oTopic = E::ModuleTopic()->GetTopicById($iTopicId))) {
                return FALSE;
            }

            // Проверим, можно ли пользователю читать этот топик, а то вдруг
            // он запросил картинки из топика закрытого блога - а так нельзя
            if (!E::ModuleACL()->IsAllowShowBlog($oTopic->getBlog(), E::User())) {
                return FALSE;
            }

            // Попытаемся найти фотосет
            /** @var ModuleMresource_EntityMresource[] $aPhotos */
            if (!(($aPhotos = E::ModuleMresource()->GetMresourcesRelByTarget('photoset', $oTopic->getId())) &&
                is_array($aPhotos) &&
                count($aPhotos) > 0)
            ) {
                return FALSE;
            }

            // Фотосет нашли, теперь из него нужно выбрать только те фото,
            // которые выбрал пользователь в параметрах from и to
            $iFrom = isset($aData['params']['from']) ? $aData['params']['from'] : 0;
            $iFrom = (int)str_replace(array('last', 'first'), array(count($aPhotos), 0), $iFrom);
            // Пользователи считают картинки с первой, а не с нулевой
            if ($iFrom) {
                $iFrom -= 1;
            }
            // Если указана длина, то правый предел игнорируем
            if (($iCount = (int)isset($aData['params']['count']) ? $aData['params']['count'] : FALSE)) {
                $iTo = $iFrom + $iCount - 1;
            } else {
                $iTo = isset($aData['params']['to']) ? $aData['params']['to'] : count($aPhotos);
                $iTo = (int)str_replace(array('last', 'first'), array(count($aPhotos), 0), $iTo);
                if ($iTo) {
                    $iTo -= 1;
                }
            }
            // Пользователь ошибочно указал диапазон. выдумывать ничего не будем,
            // просто не выведем фотосет
            if ($iTo - $iFrom < 0) {
                return FALSE;
            }
            // Сбросим ключи набора фото, так лучше считать диапазон
            $aPhotos = array_values($aPhotos);
            foreach ($aPhotos as $k => $v) {
                if ($k < $iFrom || $k > $iTo) {
                    unset ($aPhotos[$k]);
                }
            }
            if (!$aPhotos) {
                return FALSE;
            }

            $sPosition = $iTopicId = (int)isset($aData['params']['position']) ? $aData['params']['position'] : 'center';
            if (!in_array($sPosition, array('left', 'right'))) {
                $sPosition = 'center';
            }

            // Получим html-код сниппета
            $aVars = array(
                'oTopic'        => $oTopic,
                'aPhotos'       => $aPhotos,
                'sPosition'     => $sPosition,
                'sPhotosetHash' => md5(serialize($aData['params']))
            );

            $aData['result'] = trim(E::ModuleViewer()->Fetch('tpls/snippets/snippet.photoset.tpl', $aVars));

            return $aData['result'];
        }


        return FALSE;
    }

}
