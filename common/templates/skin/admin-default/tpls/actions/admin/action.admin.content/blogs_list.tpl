{extends file='./blogs.tpl'}

{block name="content-bar"}
    <div class="btn-group">
        <a href="#" class="btn btn-primary disabled"><i class="icon icon-plus"></i></a>
    </div>
    <div class="btn-group">
        <a class="btn btn-default {if $sMode=='all' || $sMode==''}active{/if}" href="{router page='admin'}content-blogs/list/">
            {$aLang.action.admin.blogs_all_types} <span class="badge badge-up">{$nBlogsTotal}</span>
        </a>
        {foreach $aBlogTypes as $oBlogType}
            <a class="btn btn-default {if $sMode==$oBlogType->GetTypeCode()}active{/if}"
               href="{router page='admin'}content-blogs/list/{$oBlogType->GetTypeCode()}/">
                {$oBlogType->GetName()} <span class="badge badge-up">{$oBlogType->GetBlogsCount()}</span>
            </a>
        {/foreach}
    </div>
{/block}

{block name="content-body"}
    <div class="span12">

        <div class="b-wbox">
            <div class="b-wbox-content nopadding">
                <table class="table table-striped table-condensed blogs-list">
                    <thead>
                    <tr>
                        <th class="span1">ID</th>
                        <th>User</th>
                        <th>Title</th>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Users</th>
                        <th>Topics</th>
                        <th>Votes</th>
                        <th>Rating</th>
                        <th class="span2">&nbsp;</th>
                    </tr>
                    </thead>

                    <tbody>
                    {foreach $aBlogs as $oBlog}
                        <tr>
                            <td class="number">{$oBlog->GetId()}</td>
                            <td>
                                <a href="{router page='admin'}users-list/profile/{$oBlog->GetOwner()->GetId()}/">{$oBlog->GetOwner()->getDisplayName()}</a>
                            </td>
                            <td class="name">
                                <a href="{$oBlog->GetUrlFull()}">{$oBlog->GetTitle()}</a>
                            </td>
                            <td class="center">{$oBlog->GetBlogDateAdd()}</td>
                            <td class="center">
                                {if $oBlog->GetBlogType()}{$oBlog->GetBlogType()->GetName()}{/if}<br/>
                                {if $oBlog->GetType()!='personal'}
                                <b>{/if}{$oBlog->GetType()}{if $oBlog->GetType()!='personal'}</b>{/if}
                            </td>
                            <td class="number">{$oBlog->GetBlogCountUser()}</td>
                            <td class="number">{$oBlog->GetBlogCountTopic()}</td>
                            <td class="number">{$oBlog->GetBlogCountVote()}</td>
                            <td class="number">{$oBlog->GetBlogRating()}</td>
                            <td class="center">
                                {if $oBlog->GetType()=='personal'}
                                    <i class="icon icon-note opacity50"></i>
                                {else}
                                    <a href="{router page='blog'}edit/{$oBlog->GetId()}/"
                                       title="{$aLang.action.admin.blog_edit}">
                                        <i class="icon icon-note"></i></a>
                                {/if}
                                <a href="#" title="{$aLang.action.admin.blog_delete}"
                                   onclick="admin.blog.del('{$oBlog->GetTitle()|escape:'html'}','{$oBlog->GetId()}', '{$oBlog->GetBlogCountTopic()}'); return false;">
                                    <i class="icon icon-trash"></i></a>
                            </td>
                        </tr>
                    {/foreach}
                    </tbody>
                </table>
            </div>
        </div>

        {include file="inc.paging.tpl"}

    </div>

    <!-- modal -->
    <div class="modal fade in" id="modal-blog_delete">
        <div class="modal-dialog">
            <div class="modal-content">

                <header class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h3>{$aLang.blog_admin_delete_title}</h3>
                </header>

                <form action="" method="POST" class="uniform">
                    <div class="modal-body">
                        <p>
                            {$aLang.action.admin.blog_del_confirm_text}
                            <strong id="blog_delete_name"></strong>
                        </p>

                        <p>
                            {$aLang.action.admin.blog_del_confirm_topics}
                            <strong id="blog_delete_topics"></strong>
                        </p>

                        <div id="blog_delete_choose">
                            <p>{$aLang.action.admin.blog_del_topics_choose}</p>

                            <p>
                                <label>
                                    <input type="radio" name="delete_topics" value="delete" checked>
                                    {$aLang.blog_delete_clear}
                                </label>
                                <label>
                                    <input type="radio" name="delete_topics" value="move">
                                    {$aLang.blog_admin_delete_move}
                                </label>
                                <select name="topic_move_to" id="topic_move_to" class="input-wide" style="display: none;">
                                    <option value=""></option>
                                    {foreach $aAllBlogs as $nBlogId=>$sBlogTitle}
                                        <option value="{$nBlogId}">{$sBlogTitle|escape:'html'}</option>
                                    {/foreach}
                                </select>
                            </p>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <input type="hidden" name="cmd" value="delete_blog"/>
                        <input type="hidden" name="delete_blog_id" value=""/>
                        <input type="hidden" name="security_key" value="{$ALTO_SECURITY_KEY}"/>
                        <input type="hidden" name="return-path" value="{Router::Url('link')}"/>
                        <button type="submit" class="btn" data-dismiss="modal" aria-hidden="true">{$aLang.text_cancel}</button>
                        <button type="submit" class="btn btn-primary">{$aLang.action.admin.blog_delete}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- /modal -->

    <script>
        var admin = admin || { };
        (function($) {
            admin.blog = admin.blog || { };
            var modal = $('#modal-blog_delete');
            admin.blog.del = function (blogTitle, blogId, topicsNum) {
                if (modal.length) {
                    $('#blog_delete_name').text(blogTitle);
                    $('#blog_delete_topics').text(topicsNum);
                    modal.find('[name=delete_blog_id]').val(blogId);
                    if (topicsNum > 0) {
                        $('#blog_delete_choose').show();
                    } else {
                        $('#blog_delete_choose').hide();
                    }
                    modal.modal('show');
                }
                return false;
            };
            $(function(){
                modal.find('[name=delete_topics]').on('change', function(){
                    if ($(this).val() == 'delete') {
                        modal.find('[id^=topic_move_to]').hide();
                    } else {
                        modal.find('[id^=topic_move_to]').show();
                    }
                });
                modal.find('[name=delete_topics]:checked').trigger('change');
            });
        })(jQuery);
    </script>
{/block}