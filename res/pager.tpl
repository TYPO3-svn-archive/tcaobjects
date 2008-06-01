<div class="pager">
	{if not($prev.alreadyhere)}
		<span class="pager{$prev.class}"><a href="{$prev.url}">{"pagebrowser.prev"|ll}</a></span>
	{/if}
	
	{foreach from=$pages item=page}
		{if $page=='fill'}
			{"pagebrowser.fill"|ll}
		{else}
			<span class="pager{$page.class}"><a href="{$page.url}">{$page.label}</a></span>
		{/if}	 
	{/foreach}
	
	{if not($next.alreadyhere)}
		<span class="pager{$next.class}"><a href="{$next.url}">{"pagebrowser.next"|ll}</a></span>
	{/if}
</div>