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
 * Экшен бработки RSS
 * Автор класса vovazol(http://livestreet.ru/profile/vovazol/)
 *
 * @package actions
 * @since   1.0
 */
class ActionRss extends Action {
    /**
     * Инициализация
     */
    public function Init() {

        $this->SetDefaultEvent('index');
        Router::SetIsShowStats(false);
    }

    /**
     * Указывает браузеру правильный content type в случае вывода RSS-ленты
     */
    protected function InitRss() {

        header('Content-Type: application/rss+xml; charset=utf-8');
    }

    /**
     * Регистрация евентов
     */
    protected function RegisterEvent() {

        $this->AddEvent('index', 'RssGood');
        $this->AddEvent('new', 'RssNew');
        $this->AddEvent('wall', 'RssWall');
        $this->AddEvent('allcomments', 'RssComments');
        $this->AddEvent('comments', 'RssTopicComments');
        $this->AddEvent('tag', 'RssTag');
        $this->AddEvent('blog', 'RssColectiveBlog');
        $this->AddEvent('personal_blog', 'RssPersonalBlog');
    }

    /**
     * Вывод RSS интересных топиков
     */
    protected function RssGood() {
        /**
         * Получаем топики
         */
        $aResult = $this->Topic_GetTopicsGood(1, Config::Get('module.topic.per_page') * 2, false);
        $aTopics = $aResult['collection'];
        /**
         * Формируем данные канала RSS
         */
        $aChannel['title'] = Config::Get('view.name');
        $aChannel['link'] = Config::Get('path.root.url');
        $aChannel['description'] = Config::Get('view.name') . ' / RSS channel';
        $aChannel['language'] = 'ru';
        $aChannel['managingEditor'] = Config::Get('general.rss_editor_mail');
        $aChannel['generator'] = Config::Get('view.name');
        /**
         * Формируем записи RSS
         */
        $topics = array();
        foreach ($aTopics as $oTopic) {
            $item['title'] = $oTopic->getTitle();
            $item['guid'] = $oTopic->getUrl();
            $item['link'] = $oTopic->getUrl();
            $item['description'] = $this->getTopicText($oTopic);
            $item['pubDate'] = $oTopic->getDateAdd();
            $item['author'] = $oTopic->getUser()->getLogin();
            $item['category'] = htmlspecialchars($oTopic->getTags());
            $topics[] = $item;
        }
        /**
         * Формируем ответ
         */
        $this->InitRss();
        $this->Viewer_Assign('aChannel', $aChannel);
        $this->Viewer_Assign('aItems', $topics);
        $this->SetTemplateAction('index');
    }

    /**
     * Вывод RSS новых топиков
     */
    protected function RssNew() {
        /**
         * Получаем топики
         */
        $aResult = $this->Topic_GetTopicsNew(1, Config::Get('module.topic.per_page') * 2, false);
        $aTopics = $aResult['collection'];
        /**
         * Формируем данные канала RSS
         */
        $aChannel['title'] = Config::Get('view.name');
        $aChannel['link'] = Config::Get('path.root.url');
        $aChannel['description'] = Config::Get('path.root.url') . ' / RSS channel';
        $aChannel['language'] = 'ru';
        $aChannel['managingEditor'] = Config::Get('general.rss_editor_mail');
        $aChannel['generator'] = Config::Get('path.root.url');
        /**
         * Формируем записи RSS
         */
        $topics = array();
        foreach ($aTopics as $oTopic) {
            $item['title'] = $oTopic->getTitle();
            $item['guid'] = $oTopic->getUrl();
            $item['link'] = $oTopic->getUrl();
            $item['description'] = $this->getTopicText($oTopic);
            $item['pubDate'] = $oTopic->getDateAdd();
            $item['author'] = $oTopic->getUser()->getLogin();
            $item['category'] = htmlspecialchars($oTopic->getTags());
            $topics[] = $item;
        }
        /**
         * Формируем ответ
         */
        $this->InitRss();
        $this->Viewer_Assign('aChannel', $aChannel);
        $this->Viewer_Assign('aItems', $topics);
        $this->SetTemplateAction('index');
    }

    /**
     * Вывод RSS последних комментариев
     */
    protected function RssComments() {
        /**
         * Получаем закрытые блоги, чтобы исключить их из выдачи
         */
        $aCloseBlogs = $this->Blog_GetInaccessibleBlogsByUser();
        /**
         * Получаем комментарии
         */
        $aResult = $this->Comment_GetCommentsAll(
            'topic', 1, Config::Get('module.comment.per_page') * 2, array(), $aCloseBlogs
        );
        $aComments = $aResult['collection'];
        /**
         * Формируем данные канала RSS
         */
        $aChannel['title'] = Config::Get('view.name');
        $aChannel['link'] = Config::Get('path.root.url');
        $aChannel['description'] = Config::Get('path.root.url') . ' / RSS channel';
        $aChannel['language'] = 'ru';
        $aChannel['managingEditor'] = Config::Get('general.rss_editor_mail');
        $aChannel['generator'] = Config::Get('path.root.url');
        /**
         * Формируем записи RSS
         */
        $comments = array();
        foreach ($aComments as $oComment) {
            if ($oComment->getTarget()) {
                $item['title'] = 'Comments: ' . $oComment->getTarget()->getTitle();
                $item['guid'] = $oComment->getTarget()->getUrl() . '#comment' . $oComment->getId();
                $item['link'] = $oComment->getTarget()->getUrl() . '#comment' . $oComment->getId();
                $item['description'] = $oComment->getText();
                $item['pubDate'] = $oComment->getDate();
                $item['author'] = $oComment->getUser()->getLogin();
                $item['category'] = 'comments';
                $comments[] = $item;
            }
        }
        /**
         * Формируем ответ
         */
        $this->InitRss();
        $this->Viewer_Assign('aChannel', $aChannel);
        $this->Viewer_Assign('aItems', $comments);
        $this->SetTemplateAction('index');
    }

    /**
     * Вывод RSS последних комментариев
     */
    protected function RssWall() {
        /**
         * Получаем комментарии
         */
        $aResult = $this->Wall_GetWall(array(), array('date_add' => 'DESC'), 1, Config::Get('module.wall.per_page'));
        /** @var ModuleWall_EntityWall[] $aWall */
        $aWall = $aResult['collection'];

        /**
         * Формируем данные канала RSS
         */
        $aChannel['title'] = Config::Get('view.name');
        $aChannel['link'] = Config::Get('path.root.url');
        $aChannel['description'] = Config::Get('path.root.url') . ' / RSS channel';
        $aChannel['language'] = 'ru';
        $aChannel['managingEditor'] = Config::Get('general.rss_editor_mail');
        $aChannel['generator'] = Config::Get('path.root.url');
        /**
         * Формируем записи RSS
         */
        $comments = array();
        foreach ($aWall as $oWall) {
            /** @var ModuleUser_EntityUser $oWallUser */
            $oWallUser = $oWall->getWallUser();
            /** @var ModuleUser_EntityUser $oUser */
            $oUser = $this->User_GetUserById($oWall->getUserId());

            $item['title'] = 'Wall: ' . $oUser->getLogin();
            $item['guid'] = $oWall->getUrlWall();
            $item['link'] = $oWall->getUrlWall();
            $item['description'] = $oWall->getText();
            $item['pubDate'] = $oWall->getDateAdd();
            $item['author'] = $oUser->getLogin();
            $item['category'] = 'wall';
            $comments[] = $item;
        }
        /**
         * Формируем ответ
         */
        $this->InitRss();
        $this->Viewer_Assign('aChannel', $aChannel);
        $this->Viewer_Assign('aItems', $comments);
        $this->SetTemplateAction('index');
    }

    /**
     * Вывод RSS комментариев конкретного топика
     */
    protected function RssTopicComments() {

        $sTopicId = $this->GetParam(0);

        // * Топик существует?
        if (!($oTopic = $this->Topic_GetTopicById($sTopicId)) || !$oTopic->getPublish() || $oTopic->getBlog()->IsPrivate()) {
            return parent::EventNotFound();
        }

        // * Получаем комментарии
        $aResult = $this->Comment_GetCommentsByFilter(
            array('target_id' => $oTopic->getId(), 'target_type' => 'topic', 'delete' => 0),
            array('comment_id' => 'desc'), 1, 100
        );
        $aComments = $aResult['collection'];

        // * Формируем данные канала RSS
        $aChannel['title'] = Config::Get('view.name');
        $aChannel['link'] = Config::Get('path.root.url');
        $aChannel['description'] = Config::Get('path.root.url') . ' / RSS channel';
        $aChannel['language'] = 'ru';
        $aChannel['managingEditor'] = Config::Get('general.rss_editor_mail');
        $aChannel['generator'] = Config::Get('path.root.url');

        // * Формируем записи RSS
        $comments = array();
        foreach ($aComments as $oComment) {
            $item['title'] = 'Comments: ' . $oTopic->getTitle();
            $item['guid'] = $oTopic->getUrl() . '#comment' . $oComment->getId();
            $item['link'] = $oTopic->getUrl() . '#comment' . $oComment->getId();
            $item['description'] = $oComment->getText();
            $item['pubDate'] = $oComment->getDate();
            $item['author'] = $oComment->getUser()->getLogin();
            $item['category'] = 'comments';
            $comments[] = $item;
        }

        // * Формируем ответ
        $this->InitRss();
        $this->Viewer_Assign('aChannel', $aChannel);
        $this->Viewer_Assign('aItems', $comments);
        $this->SetTemplateAction('index');
    }

    /**
     * Вывод RSS топиков по определенному тегу
     */
    protected function RssTag() {

        $sTag = urldecode($this->GetParam(0));
        /**
         * Получаем топики
         */
        $aResult = $this->Topic_GetTopicsByTag($sTag, 1, Config::Get('module.topic.per_page') * 2, false);
        $aTopics = $aResult['collection'];
        /**
         * Формируем данные канала RSS
         */
        $aChannel['title'] = Config::Get('view.name');
        $aChannel['link'] = Config::Get('path.root.url');
        $aChannel['description'] = Config::Get('path.root.url') . ' / RSS channel';
        $aChannel['language'] = 'ru';
        $aChannel['managingEditor'] = Config::Get('general.rss_editor_mail');
        $aChannel['generator'] = Config::Get('path.root.url');
        /**
         * Формируем записи RSS
         */
        $topics = array();
        foreach ($aTopics as $oTopic) {
            $item['title'] = $oTopic->getTitle();
            $item['guid'] = $oTopic->getUrl();
            $item['link'] = $oTopic->getUrl();
            $item['description'] = $this->getTopicText($oTopic);
            $item['pubDate'] = $oTopic->getDateAdd();
            $item['author'] = $oTopic->getUser()->getLogin();
            $item['category'] = htmlspecialchars($oTopic->getTags());
            $topics[] = $item;
        }
        /**
         * Формируем ответ
         */
        $this->InitRss();
        $this->Viewer_Assign('aChannel', $aChannel);
        $this->Viewer_Assign('aItems', $topics);
        $this->SetTemplateAction('index');
    }

    /**
     * Вывод RSS топиков из коллективного блога
     */
    protected function RssColectiveBlog() {

        $sBlogUrl = $this->GetParam(0);
        /**
         * Если блог существует, то получаем записи
         */
        if (!$sBlogUrl || !($oBlog = $this->Blog_GetBlogByUrl($sBlogUrl)) || $oBlog->getType() == "close") {
            return parent::EventNotFound();
        } else {
            $aOpenBlogTypes = $this->Blog_GetOpenBlogTypes();
            if (!in_array($oBlog->getType(), $aOpenBlogTypes)) {
                return parent::EventNotFound();
            }
            $aResult = $this->Topic_GetTopicsByBlog($oBlog, 1, Config::Get('module.topic.per_page') * 2, 'good');
        }
        $aTopics = $aResult['collection'];
        /**
         * Формируем данные канала RSS
         */
        $aChannel['title'] = Config::Get('view.name');
        $aChannel['link'] = Config::Get('path.root.url');
        $aChannel['description'] = Config::Get('path.root.url') . ' / ' . $oBlog->getTitle() . ' / RSS channel';
        $aChannel['language'] = 'ru';
        $aChannel['managingEditor'] = Config::Get('general.rss_editor_mail');
        $aChannel['generator'] = Config::Get('path.root.url');
        /**
         * Формируем записи RSS
         */
        $topics = array();
        foreach ($aTopics as $oTopic) {
            $item['title'] = $oTopic->getTitle();
            $item['guid'] = $oTopic->getUrl();
            $item['link'] = $oTopic->getUrl();
            $item['description'] = $this->getTopicText($oTopic);
            $item['pubDate'] = $oTopic->getDateAdd();
            $item['author'] = $oTopic->getUser()->getLogin();
            $item['category'] = htmlspecialchars($oTopic->getTags());
            $topics[] = $item;
        }
        /**
         * Формируем ответ
         */
        $this->InitRss();
        $this->Viewer_Assign('aChannel', $aChannel);
        $this->Viewer_Assign('aItems', $topics);
        $this->SetTemplateAction('index');
    }

    /**
     * Вывод RSS топиков из персонального блога или всех персональных
     */
    protected function RssPersonalBlog() {

        $this->sUserLogin = $this->GetParam(0);
        if (!$this->sUserLogin) {
            /**
             * RSS-лента всех записей из персональных блогов
             */
            $aResult = $this->Topic_GetTopicsPersonal(1, Config::Get('module.topic.per_page') * 2);
        } elseif (!$oUser = $this->User_GetUserByLogin($this->sUserLogin)) {
            return parent::EventNotFound();
        } else {
            /**
             * RSS-лента записей персонального блога указанного пользователя
             */
            $aResult = $this->Topic_GetTopicsPersonalByUser(
                $oUser->getId(), 1, 1, Config::Get('module.topic.per_page') * 2
            );
        }
        $aTopics = $aResult['collection'];
        /**
         * Формируем данные канала RSS
         */
        $aChannel['title'] = Config::Get('view.name');
        $aChannel['link'] = Config::Get('path.root.url');
        $aChannel['description'] = ($this->sUserLogin)
            ? Config::Get('path.root.url') . ' / ' . $oUser->getLogin() . ' / RSS channel'
            : Config::Get('path.root.url') . ' / RSS channel';
        $aChannel['language'] = 'ru';
        $aChannel['managingEditor'] = Config::Get('general.rss_editor_mail');
        $aChannel['generator'] = Config::Get('path.root.url');
        /**
         * Формируем записи RSS
         */
        $topics = array();
        foreach ($aTopics as $oTopic) {
            $item['title'] = $oTopic->getTitle();
            $item['guid'] = $oTopic->getUrl();
            $item['link'] = $oTopic->getUrl();
            $item['description'] = $this->getTopicText($oTopic);
            $item['pubDate'] = $oTopic->getDateAdd();
            $item['author'] = $oTopic->getUser()->getLogin();
            $item['category'] = htmlspecialchars($oTopic->getTags());
            $topics[] = $item;
        }
        /**
         * Формируем ответ
         */
        $this->InitRss();
        $this->Viewer_Assign('aChannel', $aChannel);
        $this->Viewer_Assign('aItems', $topics);
        $this->SetTemplateAction('index');
    }

    /**
     * Формирует текст топика для RSS
     *
     */
    protected function getTopicText($oTopic) {

        $sText = $oTopic->getTextShort();
        if ($oTopic->getTextShort() != $oTopic->getText()) {
            $sText .= "<br><a href=\"{$oTopic->getUrl()}#cut\" title=\"{$this->Lang_Get('topic_read_more')}\">";
            if ($oTopic->getCutText()) {
                $sText .= htmlspecialchars($oTopic->getCutText());
            } else {
                $sText .= $this->Lang_Get('topic_read_more');
            }
            $sText .= "</a>";
        }
        return $sText;
    }
}

// EOF