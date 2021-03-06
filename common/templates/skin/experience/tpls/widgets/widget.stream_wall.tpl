 {* Тема оформления Experience v.1.0  для Alto CMS      *}
 {* @licence     CC Attribution-ShareAlike   *}

{if $aWall}
    {foreach $aWall as $oWall}
        {$oUser=$oWall->getUser()}
        {$oWallUser=$oWall->getWallUser()}

        <div id="js-title-comment-{$oWall->getId()}" class="feed-topic"
             title="{$oWall->getText()|strip_tags|trim|truncate:150:'...'|escape:'html'}">
            <ul>
                <li class="user-block">
                    <img src="{$oUser->getAvatarUrl(20)}" alt="{$oUser->getDisplayName()}"/>
                    <a class="userlogo link link-dual link-lead link-clear" href="{$oUser->getProfileUrl()}">
                        {$oUser->getDisplayName()}
                    </a>
                </li>
                <li class="date-block">
                    <span class="date">{$oWall->getDateAdd()|date_format:'d.m.Y'}</span>
                    <span class="time">{$oWall->getDateAdd()|date_format:'H:i'}</span>
                </li>
            </ul>
            <div class="feed-topic-text">
                {if $oUser->getId() == $oWallUser->getId()}
                    {$aLang.widget_stream_on_his_wall}
                {else}
                    {$aLang.widget_stream_on_wall}
                    <a href="{$oWallUser->getProfileUrl()}" class="author">{$oWallUser->getDisplayName()}</a>
                {/if}
                <br/>
                <a href="{$oWall->getUrlWall()}" class="stream-topic">{$oWall->getText()|strip_tags|trim|truncate:150:'...'|escape:'html'}</a>
                <span class="stream-topic text-danger"> - <i class="fa fa-comments-o"></i>{$oWall->getCountReply()}</span>
            </div>
        </div>
    {/foreach}
{else}
    <div class="bg-warning">{$aLang.widget_stream_wall_no}</div>
{/if}