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
 * Модуль управления рейтингами и силой
 *
 * @package modules.rating
 * @since   1.0
 */
class PluginRating_ModuleRating extends PluginRating_Inherit_ModuleRating {

    /**
     * Инициализация модуля
     *
     */
    public function Init() {

    }

    /**
     * Расчет рейтинга при голосовании за комментарий
     *
     * @param ModuleUser_EntityUser       $oUser       Объект пользователя, который голосует
     * @param ModuleComment_EntityComment $oComment    Объект комментария
     * @param int                         $iValue
     *
     * @return int
     */
    public function VoteComment($oUser, $oComment, $iValue) {
        /**
         * Устанавливаем рейтинг комментария
         */
        $oComment->setRating($oComment->getRating() + $iValue);
        /**
         * Начисляем силу автору коммента, используя логарифмическое распределение
         */
        $skill = $oUser->getSkill();
        $iMinSize = C::Get('plugin.rating.comment_min_change');//0.004;
        $iMaxSize = C::Get('plugin.rating.comment_max_change');//0.5;
        $iSizeRange = $iMaxSize - $iMinSize;
        $iMinCount = log(0 + 1);
        $iMaxCount = log(C::Get('plugin.rating.comment_max_rating') + 1);//500
        $iCountRange = $iMaxCount - $iMinCount;
        if ($iCountRange == 0) {
            $iCountRange = 1;
        }
        if ($skill > C::Get('plugin.rating.comment_left_border') and $skill < C::Get('plugin.rating.comment_right_border')) {//50-200
            $skill_new = $skill / C::Get('plugin.rating.comment_mid_divider');//70
        } elseif ($skill >= C::Get('plugin.rating.comment_right_border')) {//200
            $skill_new = $skill / C::Get('plugin.rating.comment_right_divider');//10
        } else {
            $skill_new = $skill / C::Get('plugin.rating.comment_left_divider');//130
        }
        $iDelta = $iMinSize + (log($skill_new + 1) - $iMinCount) * ($iSizeRange / $iCountRange);
        /**
         * Сохраняем силу
         */
        $oUserComment = E::ModuleUser()->GetUserById($oComment->getUserId());
        $iSkillNew = $oUserComment->getSkill() + $iValue * $iDelta;
        $iSkillNew = ($iSkillNew < 0) ? 0 : $iSkillNew;
        $oUserComment->setSkill($iSkillNew);
        E::ModuleUser()->Update($oUserComment);
        return $iValue;
    }

    /**
     * Расчет рейтинга и силы при гоосовании за топик
     *
     * @param ModuleUser_EntityUser   $oUser     Объект пользователя, который голосует
     * @param ModuleTopic_EntityTopic $oTopic    Объект топика
     * @param int                     $iValue
     *
     * @return int
     */
    public function VoteTopic($oUser, $oTopic, $iValue) {

        $skill = $oUser->getSkill();
        /**
         * Устанавливаем рейтинг топика
         */
        $iDeltaRating = $iValue * C::Get('plugin.rating.rating_topic_k1');//1
        if ($skill >= C::Get('plugin.rating.rating_topic_border_1') and $skill < C::Get('plugin.rating.rating_topic_border_2')) { // 100-250
            $iDeltaRating = $iValue * C::Get('plugin.rating.rating_topic_k2');//2
        } elseif ($skill >= C::Get('plugin.rating.rating_topic_border_2') and $skill < C::Get('plugin.rating.rating_topic_border_3')) { //250-400
            $iDeltaRating = $iValue * C::Get('plugin.rating.rating_topic_k3');//3
        } elseif ($skill >= C::Get('plugin.rating.rating_topic_border_3')) { //400
            $iDeltaRating = $iValue * C::Get('plugin.rating.rating_topic_k4');//4
        }
        $oTopic->setRating($oTopic->getRating() + $iDeltaRating);
        /**
         * Начисляем силу и рейтинг автору топика, используя логарифмическое распределение
         */
        $iMinSize = C::Get('plugin.rating.topic_min_change');//0.1;
        $iMaxSize = C::Get('plugin.rating.topic_max_change');//8;
        $iSizeRange = $iMaxSize - $iMinSize;
        $iMinCount = log(0 + 1);
        $iMaxCount = log(C::Get('plugin.rating.topic_max_rating') + 1);
        $iCountRange = $iMaxCount - $iMinCount;
        if ($iCountRange == 0) {
            $iCountRange = 1;
        }
        if ($skill > C::Get('plugin.rating.topic_left_border') and $skill < C::Get('plugin.rating.topic_right_border')) {//200
            $skill_new = $skill / C::Get('plugin.rating.topic_mid_divider');//70;
        } elseif ($skill >= C::Get('plugin.rating.topic_right_border')) {//200
            $skill_new = $skill / C::Get('plugin.rating.topic_right_divider');//10;
        } else {
            $skill_new = $skill / C::Get('plugin.rating.topic_left_divider');//100;
        }
        $iDelta = $iMinSize + (log($skill_new + 1) - $iMinCount) * ($iSizeRange / $iCountRange);
        /**
         * Сохраняем силу и рейтинг
         */
        $oUserTopic = E::ModuleUser()->GetUserById($oTopic->getUserId());
        $iSkillNew = $oUserTopic->getSkill() + $iValue * $iDelta;
        $iSkillNew = ($iSkillNew < 0) ? 0 : $iSkillNew;
        $oUserTopic->setSkill($iSkillNew);
        $oUserTopic->setRating($oUserTopic->getRating() + $iValue * $iDelta / C::Get('plugin.rating.topic_auth_coef'));//2.73
        E::ModuleUser()->Update($oUserTopic);
        return $iDeltaRating;
    }

    /**
     * Расчет рейтинга и силы при голосовании за блог
     *
     * @param ModuleUser_EntityUser $oUser    Объект пользователя, который голосует
     * @param ModuleBlog_EntityBlog $oBlog    Объект блога
     * @param int                   $iValue
     *
     * @return int
     */
    public function VoteBlog($oUser, $oBlog, $iValue) {
        /**
         * Устанавливаем рейтинг блога, используя логарифмическое распределение
         */
        $skill = $oUser->getSkill();
        $iMinSize = C::Get('plugin.rating.blog_min_change');//1.13;
        $iMaxSize = C::Get('plugin.rating.blog_max_change');//15;
        $iSizeRange = $iMaxSize - $iMinSize;
        $iMinCount = log(0 + 1);
        $iMaxCount = log(C::Get('plugin.rating.blog_max_rating') + 1);//500
        $iCountRange = $iMaxCount - $iMinCount;
        if ($iCountRange == 0) {
            $iCountRange = 1;
        }
        if ($skill > C::Get('plugin.rating.blog_left_border') and $skill < C::Get('plugin.rating.blog_right_border')) {//50-200
            $skill_new = $skill / C::Get('plugin.rating.blog_mid_divider');//20;
        } elseif ($skill >= C::Get('plugin.rating.blog_right_border')) {//200
            $skill_new = $skill / C::Get('plugin.rating.blog_right_divider');//10;
        } else {
            $skill_new = $skill / C::Get('plugin.rating.blog_left_divider');//50;
        }
        $iDelta = $iMinSize + (log($skill_new + 1) - $iMinCount) * ($iSizeRange / $iCountRange);
        /**
         * Сохраняем рейтинг
         */
        $oBlog->setRating($oBlog->getRating() + $iValue * $iDelta);
        return $iValue * $iDelta;
    }

    /**
     * Расчет рейтинга и силы при голосовании за пользователя
     *
     * @param ModuleUser_EntityUser $oUser
     * @param ModuleUser_EntityUser $oUserTarget
     * @param int                   $iValue
     *
     * @return float
     */
    public function VoteUser($oUser, $oUserTarget, $iValue) {
        /**
         * Начисляем силу и рейтинг юзеру, используя логарифмическое распределение
         */
        $skill = $oUser->getSkill();
        $iMinSize = C::Get('plugin.rating.user_min_change');//0.42;
        $iMaxSize = C::Get('plugin.rating.user_max_change');//3.2;
        $iSizeRange = $iMaxSize - $iMinSize;
        $iMinCount = log(0 + 1);
        $iMaxCount = log(C::Get('plugin.rating.user_max_rating') + 1); // 500
        $iCountRange = $iMaxCount - $iMinCount;
        if ($iCountRange == 0) {
            $iCountRange = 1;
        }
        if ($skill > C::Get('plugin.rating.user_left_border') and $skill < C::Get('plugin.rating.user_right_border')) { // 50-200
            $skill_new = $skill / C::Get('plugin.rating.user_mid_divider'); //70
        } elseif ($skill >= C::Get('plugin.rating.user_right_border')) { // 200
            $skill_new = $skill / C::Get('plugin.rating.user_right_divider'); //2
        } else {
            $skill_new = $skill / C::Get('plugin.rating.user_left_divider'); //40
        }
        $iDelta = $iMinSize + (log($skill_new + 1) - $iMinCount) * ($iSizeRange / $iCountRange);
        /**
         * Определяем новый рейтинг
         */
        $iRatingNew = $oUserTarget->getRating() + $iValue * $iDelta;
        $oUserTarget->setRating($iRatingNew);
        return $iValue * $iDelta;
    }


    /**
     * Расчет рейтинга блога
     *
     * @return bool
     */
    public function RecalculateBlogRating() {

        /*
         * Получаем статистику
         */
        $aBlogStat = E::ModuleBlog()->GetBlogsData(array('personal'));

        foreach ($aBlogStat as $oBlog) {

            $fRating = 0;

            //*** Учет суммы голосов за топики с весовым коэффициентом
            $fRating = $fRating + Config::Get('module.rating.blog.topic_rating_sum') * $oBlog->getSumRating();

            //*** Учет количества топиков с весовым коэффициентом
            $fRating = $fRating + Config::Get('module.rating.blog.count_users') * $oBlog->getCountUser();

            //*** Учет количества топиков с весовым коэффициентом
            $fRating = $fRating + Config::Get('module.rating.blog.count_topic') * $oBlog->getCountTopic();

            $oBlog->setRating($fRating);
            E::ModuleBlog()->UpdateBlog($oBlog);

        }

        return true;
    }

}

// EOF