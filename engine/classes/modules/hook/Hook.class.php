<?php
/*---------------------------------------------------------------------------
 * @Project: Alto CMS
 * @Project URI: http://altocms.com
 * @Description: Advanced Community Engine
 * @Copyright: Alto CMS Team
 * @License: GNU GPL v2 & MIT
 *----------------------------------------------------------------------------
 * Based on
 *   LiveStreet Engine Social Networking by Mzhelskiy Maxim
 *   Site: www.livestreet.ru
 *   E-mail: rus.engine@gmail.com
 *----------------------------------------------------------------------------
 */

/**
 * Модуль обработки хуков(hooks)
 * В различных местах кода могут быть определеные вызовы хуков, например:
 * <pre>
 * E::ModuleHook()->Run('topic_edit_before', array('oTopic'=>$oTopic,'oBlog'=>$oBlog));
 * </pre>
 * Данный вызов "вешает" хук "topic_edit_before"
 * Чтобы повесить обработчик на этот хук, его нужно объявить, например, через файл в /classes/hooks/HookTest.class.php
 * <pre>
 * class HookTest extends Hook {
 *    // Регистрируем хуки (вешаем обработчики)
 *    public function RegisterHook() {
 *        $this->AddHook('topic_edit_before','TopicEdit');
 *    }
 *    // обработчик хука
 *    public function TopicEdit($aParams) {
 *        $oTopic=$aParams['oTopic'];
 *        $oTopic->setTitle('My title!');
 *    }
 * }
 * </pre>
 * В данном примере после редактирования топика заголовок у него поменяется на "My title!"
 *
 * Если хук объявлен в шаблоне, например,
 * <pre>
 * {hook run='html_head_end'}
 * </pre>
 * То к имени хука автоматически добаляется префикс "template_" и обработчик на него вешать нужно так:
 * <pre>
 * $this->AddHook('template_html_head_end','InjectHead');
 * </pre>
 *
 * Так же существуют блочные хуки, который объявляются в шаблонах так:
 * <pre>
 * {hookb run="registration_captcha"}
 * ... html ...
 * {/hookb}
 * </pre>
 * Они позволяют заменить содержимое между {hookb ..} {/hookb} или добавть к нему произвольный контент. К имени такого хука добавляется префикс "template_block_"
 * <pre>
 * class HookTest extends Hook {
 *    // Регистрируем хуки (вешаем обработчики)
 *    public function RegisterHook() {
 *        $this->AddHook('template_block_registration_captcha','MyCaptcha');
 *    }
 *    // обработчик хука
 *    public function MyCaptcha($aParams) {
 *        $sContent=$aParams['content'];
 *        return $sContent.'My captcha!';
 *    }
 * }
 * </pre>
 * В данном примере в конце вывода каптчи будет добавлено "My captcha!"
 * Обратите внимаете, что в обработчик в параметре "content" передается исходное содержание блока.
 *
 * @package engine.modules
 * @since   1.0
 */
class ModuleHook extends Module {
    /**
     * Содержит список хуков
     *
     * @var array( 'name' => array(
     *        array(
     *            'type' => 'module' | 'hook' | 'function',
     *            'callback' => 'callback_name',
     *            'priority'    => 1,
     *            'params' => array()
     *        ),
     *    ),
     * )
     */
    protected $aHooks = array();

    protected $aHooksOrders = array();
    /**
     * Список объектов обработки хукков, для их кешировани
     *
     * @var array
     */
    protected $aHooksObject = array();

    /**
     * Инициализация модуля
     *
     */
    public function Init() {

    }

    /**
     * Возвращает информацию о том, включен хук или нет
     *
     * @param string $sHookName
     *
     * @return bool
     */
    public function IsEnabled($sHookName) {

        return isset($this->aHooks[$sHookName]);
    }

    /**
     * Добавление обработчика на хук
     *
     * @param string $sName        Имя хука
     * @param string $sType        Тип хука, возможны: module, function, hook
     * @param string $sCallBack    Функция/метод обработки хука
     * @param int    $iPriority    Приоритер обработки, чем выше, тем раньше сработает хук относительно других
     * @param array  $aParams      Список дополнительных параметров, анпример, имя класса хука
     *
     * @return bool
     */
    public function Add($sName, $sType, $sCallBack, $iPriority = 1, $aParams = array()) {

        $sName = strtolower($sName);
        $sType = strtolower($sType);
        if (!in_array($sType, array('module', 'hook', 'function', 'template'))) {
            return false;
        }
        $this->aHooks[$sName][] = array(
            'type' => $sType,
            'callback' => $sCallBack,
            'params' => $aParams,
            'priority' => (int)$iPriority
        );
        if (!empty($this->aHooksOrders)) {
            $this->aHooksOrders = array();
        }
        return true;
    }

    /**
     * Добавляет обработчик хука с типом "module"
     * Позволяет в качестве обработчика использовать метод модуля
     *
     * @see Add
     *
     * @param string $sName        Имя хука
     * @param string $sCallBack    Полное имя метода обработки хука, например, "Mymodule_CallBack"
     * @param int    $iPriority    Приоритер обработки, чем выше, тем раньше сработает хук относительно других
     *
     * @return bool
     */
    public function AddExecModule($sName, $sCallBack, $iPriority = 1) {

        return $this->Add($sName, 'module', $sCallBack, $iPriority);
    }

    /**
     * Добавляет обработчик хука с типом "function"
     * Позволяет в качестве обработчика использовать функцию
     *
     * @see Add
     *
     * @param string $sName        Имя хука
     * @param string $sCallBack    Функция обработки хука, например, "var_dump"
     * @param int    $iPriority    Приоритер обработки, чем выше, тем раньше сработает хук относительно других
     * @param array  $aParams      Параметры
     *
     * @return bool
     */
    public function AddExecFunction($sName, $sCallBack, $iPriority = 1, $aParams = array()) {

        return $this->Add($sName, 'function', $sCallBack, $iPriority, $aParams);
    }

    /**
     * Добавляет обработчик хука с типом "hook"
     * Позволяет в качестве обработчика использовать метод хука(класса хука из каталога /classes/hooks/)
     *
     * @see Add
     * @see Hook::AddHook
     *
     * @param string $sName        Имя хука
     * @param string $sCallBack    Метод хука, например, "InitAction"
     * @param int    $iPriority    Приоритер обработки, чем выше, тем раньше сработает хук относительно других
     * @param array  $aParams      Параметры
     *
     * @return bool
     */
    public function AddExecHook($sName, $sCallBack, $iPriority = 1, $aParams = array()) {

        return $this->Add($sName, 'hook', $sCallBack, $iPriority, $aParams);
    }

    /**
     * Запускает обаботку хуков
     *
     * @param string $sName Имя хука
     * @param array  $aVars Список параметров хука, передаются в обработчик
     *
     * @return array
     */
    public function Run($sName, $aVars = array()) {

        $xResult = array();
        $sName = strtolower($sName);
        $bTemplateHook = strpos($sName, 'template_') === 0 ? true : false;

        if (isset($this->aHooks[$sName])) {
            if (empty($this->aHooksOrders[$sName])) {
                $this->aHooksOrders = array();
                $iCount = count($this->aHooks[$sName]);
                for ($iHookNum = 0; $iHookNum < $iCount; $iHookNum++) {
                    $this->aHooksOrders[$sName][$iHookNum] = $this->aHooks[$sName][$iHookNum]['priority'];
                }
                arsort($this->aHooksOrders[$sName], SORT_NUMERIC);
            }

            // Runs hooks in priority order
            foreach ($this->aHooksOrders[$sName] as $iHookNum => $iPr) {
                $aHook = $this->aHooks[$sName][$iHookNum];
                if ($bTemplateHook || $aHook['type'] == 'template') {
                    if (isset($aHook['params']['template']) && !isset($aVars['template'])) {
                        $aVars['template'] = $aHook['params']['template'];
                    }
                    // * Если это шаблонный хук то сохраняем результат
                    $xResult['template_result'][] = $this->RunType($aHook, $aVars);
                } else {
                    $xResult = $this->RunType($aHook, $aVars);
                }
            }
        }
        return $xResult;
    }

    /**
     * Запускает обработчик хука в зависимости от типа обработчика
     *
     * @param array $aHook    Данные хука
     * @param array $aVars    Параметры переданные в хук
     *
     * @return mixed|null
     */
    protected function RunType($aHook, &$aVars) {

        $xResult = null;
        switch ($aHook['type']) {
            case 'module':
                $xResult = call_user_func_array(array($this, $aHook['callback']), array(&$aVars));
                break;
            case 'function':
                $xResult = call_user_func_array($aHook['callback'], array(&$aVars));
                break;
            case 'hook':
                $sHookClass = isset($aHook['params']['sClassName']) ? $aHook['params']['sClassName'] : null;
                if ($sHookClass && class_exists($sHookClass)) {
                    if (isset($this->aHooksObject[$sHookClass])) {
                        $oHook = $this->aHooksObject[$sHookClass];
                    } else {
                        $oHook = new $sHookClass;
                        $this->aHooksObject[$sHookClass] = $oHook;
                    }
                    $xResult = call_user_func_array(array($oHook, $aHook['callback']), array(&$aVars));
                }
                break;
            default:
                if (is_callable($aHook['callback'])) {
                    $xResult = call_user_func_array($aHook['callback'], array(&$aVars));
                }
                break;
        }
        return $xResult;
    }
}

// EOF