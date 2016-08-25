<?

if($api->user()['type']==API::ACCOUNT_TYPE_PARTNER){

    header("Location: /clients");
    exit;
} else{
    header("Location: /tasks");
    exit;
}

