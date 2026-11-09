<?php


namespace app\modules\admin\controllers\importar;


use app\helpers\APIMarjo;

class bet365Controller extends marjosportsController
{

    public function __construct()
    {
        $this->api = new APIMarjo('bet365');
    }

}
