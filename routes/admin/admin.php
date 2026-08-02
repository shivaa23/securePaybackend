<?php
use App\Http\Controllers\AdminController;
use App\Http\Controllers\CalbackController;
use Illuminate\Support\Facades\Route;


$router->group(['prefix' => 'admin'], function () use ($router) {
    $router->get('createbackup', 'AdminController@createbackup'); //ok
    $router->get('setw2w1Limit', 'AdminController@setw2w1Limit'); //ok
    $router->get('getUsers', 'AdminController@getUsers'); //ok
    $router->post('getUsers', 'AdminController@getUsers'); //ok
    $router->get('getUsersNew', 'AdminController@getUsersNew'); //ok
    $router->post('getUsersNew', 'AdminController@getUsersNew'); //ok
    $router->get('getUserById', 'AdminController@getUserById'); //ok
    $router->get('getUserAdAsm', 'AdminController@getUserAdAsm'); //ok
    $router->post('updateUser', 'AdminController@updateUser'); //ok
    $router->post('getUserServices', 'AdminController@getUserServices'); //ok
    $router->post('updateServices', 'AdminController@updateServices'); //ok
    $router->get('getUsersForFilter', 'AdminController@getUsersForFilter'); //ok
    $router->post('getTransactions', 'AdminController@getTransactions'); //ok
    $router->get('getOldTransactions', 'AdminController@getOldTransactions'); //ok
    $router->post('checkTxnStatus', 'AdminController@checkTxnStatus'); //ok
    $router->post('changeTxnStatus', 'AdminController@changeTxnStatus'); //ok
    $router->get('getIssues', 'AdminController@getIssues'); //ok
    $router->post('updateIssue', 'AdminController@updateIssue'); //ok
    $router->get('getTransactions', 'AdminController@getTransactions'); //ok
    $router->get('getCrRequests', 'AdminController@getCrRequests'); //OK
    $router->post('crActions', 'AdminController@crActions'); //ok
    $router->get('getCrRequestsForAsm', 'AdminController@getCrRequestsForAsm'); //OK
    $router->post('createNewAcc', 'AdminController@createNewAcc'); //ok
    $router->get('getAccounts', 'AdminController@getAccounts'); //OK
    $router->post('accBalance', 'AdminController@accBalance'); //OK
    $router->post('updateAccounts', 'AdminController@updateAccounts'); //OK
    $router->get('getAccStatement', 'AdminController@getAccStatement'); //ok
    $router->get('getBanks', 'AdminController@getBanks'); //ok
    $router->get('getBankStatement', 'AdminController@getBankStatement'); //ok
    $router->get('getPendingBankTxn', 'AdminController@getPendingBankTxn'); //ok
    $router->get('getMessages', 'AdminController@getMessages'); //ok
    $router->get('getOutMessages', 'AdminController@getOutMessages'); //ok
    $router->get('getWebHooks', 'AdminController@getWebHooks'); //ok
    $router->get('getOperators', 'AdminController@getOperators'); //ok
    $router->post('createOperator', 'AdminController@createOperator'); //ok
    $router->post('createPlan', 'AdminController@createPlan'); //ok
    $router->get('getPlans', 'AdminController@getPlans'); //ok
    $router->post('editPlan', 'AdminController@editPlan'); //ok
    $router->post('deletePlan', 'AdminController@deletePlan'); //ok
    $router->post('addBankTxn', 'AdminController@addBankTxn'); //ok
    $router->post('deleteBankTxn', 'AdminController@deleteBankTxn'); //ok
    $router->post('addAccTxn', 'AdminController@addAccTxn'); //ok
    $router->post('deleteTxn', 'AdminController@deleteTxn'); //ok
    $router->get('getRoutes', 'AdminController@getRoutes'); //ok
    $router->post('createRoute', 'AdminController@createRoute'); //ok
    $router->post('changeRoute', 'AdminController@changeRoute'); //ok
    $router->get('getCategories', 'AdminController@getCategories'); //ok
    $router->get('getUserCount', 'AdminController@getUserCount'); //ok
    $router->post('exportToExcel', 'AdminController@exportToExcel'); //ok
    $router->post(
        'changeOperatorStatus',
        'AdminController@changeOperatorStatus'
    ); //ok
    $router->post('blockUnblock', 'AdminController@blockUnblock'); //ok
    //$router->post('apexCallBack', 'CallbackController@apexCallBack');//ok
    $router->post('dialMyTripApi', 'CallbackController@dialMyTripApi'); //ok
    $router->post('fingPayCmsApi', 'CallbackController@fingPayCmsApi'); //ok
    $router->get('callBackIpay', 'CallbackController@callBackIpay');
    $router->get('razorPayStatus', 'CallbackController@razorPayStatus');
    $router->post('razorPayStatus', 'CallbackController@razorPayStatus');
    $router->get('aOneCallBack', 'CallbackController@aOneCallBack');
    $router->post('aOneCallBack', 'CallbackController@aOneCallBack');
    $router->post('pspOnboarding', 'CallbackController@pspOnboarding');
    $router->get('pspOnboarding', 'CallbackController@pspOnboarding');
    $router->post('message', 'CallbackController@message');
    $router->post('payGoalCallBack', 'CallbackController@payGoalCallBack');
    $router->get('payGoalCallBack', 'CallbackController@payGoalCallBack');
    $router->post('payGoalCallBack2', 'CallbackController@payGoalCallBack2');
    $router->get('payGoalCallBack2', 'CallbackController@payGoalCallBack2');
    $router->post('fingPayCallBack', 'CallbackController@fingPayCallBack');
    $router->post('fingPayCallBack', 'CallbackController@fingPayCallBack');
    $router->post('rblCallBack', 'CallbackController@rblCallBack');
    $router->post('credoPayCallBack', 'CallbackController@credoPayCallBack');
    $router->post('callBackPaysprint', 'CallbackController@callBackPaysprint');
    $router->post('callBackFrenzo', 'CallbackController@callBackFrenzo');
    $router->post('callBackWaayu', 'CallbackController@callBackWaayu');
    $router->post('callbackPr', 'CallbackController@callbackPr'); //gyan
    $router->post('callBackVdpay', 'CallbackController@callBackVdpay'); //gyan

    $router->post('changeAd', 'AdminController@changeAd'); //ok
    $router->post('orderStatus', 'AdminController@orderStatus'); //To Be Checked
    $router->get('test', 'AdminController@test');
    $router->post('createNotification', 'AdminController@createNotification');
    $router->post('updateNotification', 'AdminController@updateNotification');
    $router->get('getNotifications', 'AdminController@getNotifications');
    // $router->post('getAllUsersRoleWise', 'AdminController@getAllUsersRoleWise');
    $router->post('deleteNotification', 'AdminController@deleteNotification');
    $router->get('getCount', 'AdminController@getCount');
    $router->get('apiBalances', 'AdminController@apiBalances');
    $router->post('walletDebit', 'AdminController@walletDebit');
    $router->post('verifyPan', 'AdminController@verifyPan'); //anjali
    $router->post('verifyAadhaar', 'AdminController@verifyAadhaar'); //anjali
    $router->post('userProfitability', 'AdminController@userProfitability'); //anjali
    $router->post('deleteUser', 'AdminController@deleteUser'); //anjali
    $router->post('accountLimit', 'AdminController@accountLimit'); //anjali
    $router->get('accountLimit', 'AdminController@accountLimit'); //anjali̦
    $router->delete('accountLimit', 'AdminController@accountLimit'); //anjali̦
    $router->post('updateOperator', 'AdminController@updateOperator'); //anjali̦
    $router->get('charges', 'AdminController@charges'); //anjali̦
    $router->post('charges', 'AdminController@charges'); //anjali̦
    $router->delete('charges', 'AdminController@charges'); //anjali̦
    $router->get('pgOrders', 'AdminController@pgOrders');// anjali
    $router->get('prabhuCharge', 'AdminController@prabhuCharge');// anjali
    $router->post('prabhuCharge', 'AdminController@prabhuCharge'); //anjali̦
    $router->delete('prabhuCharge', 'AdminController@prabhuCharge'); //anjali̦
    $router->get('service', 'AdminController@service'); //anjali̦
    $router->post('service', 'AdminController@service'); //anjali̦
    $router->get('userKeys', 'AdminController@userKeys'); //anjali̦
    $router->post('userKeys', 'AdminController@userKeys'); //anjali̦
    $router->delete('userKeys', 'AdminController@userKeys'); //anjali̦
    $router->post('beneKycDoc', 'AdminController@beneKycDoc'); //anjali̦
    $router->get('beneKycDoc', 'AdminController@beneKycDoc'); //anjali̦
    $router->get('payoutBene', 'AdminController@payoutBene'); //anjali̦
    $router->post('createVa', 'AdminController@createVa'); //gyan
    $router->get('updateWalletRblAlert', 'AdminController@updateWalletRblAlert'); //gyan
    $router->get('virtualAccounts', 'AdminController@virtualAccounts'); //gyan
    $router->post('virtualAccounts', 'AdminController@virtualAccounts'); //gyan
    $router->post('vaTransactions', 'AdminController@vaTransactions'); //gyan
    $router->get('vaTransactions', 'AdminController@vaTransactions'); //gyan
    $router->get('updateRblBank', 'AdminController@updateRblBank'); //gyan
    $router->get('AutoCrActions', 'AdminController@AutoCrActions'); //gyan
    $router->get('bankDepData', 'AdminController@bankDepData'); //gyan
    $router->get('blockedAccounts', 'AdminController@blockedAccounts'); //gyan
    $router->post('blockedAccounts', 'AdminController@blockedAccounts'); //gyan
    $router->get('news', 'AdminController@news'); //gyan
    $router->post('news', 'AdminController@news'); //gyan
    $router->delete('news', 'AdminController@news'); //gyan
    $router->post('updateSuperRate', 'AdminController@updateSuperRate'); //gyan
    $router->post('createBank', 'AdminController@createBank'); //gyan
    $router->post('aggreementDate', 'AdminController@aggreementDate'); //gyan
    $router->post('approveRejectKyc', 'AdminController@approveRejectKyc'); //gyan
    $router->get('logOutAll', 'AdminController@logOutAll'); //gyan
    $router->post('changeBankStatus', 'AdminController@changeBankStatus'); //gyan
    $router->get('remLimit', 'AdminController@remLimit'); //gyan
    $router->post('resendTransaction', 'AdminController@resendTransaction'); //gyan
    $router->get('getMerchantsData', 'AdminController@getMerchantsData'); //anjali
    $router->post('mobileUpdateVerify', 'AdminController@mobileUpdateVerify'); //anjali
    $router->post('mobileUpdate', 'AdminController@mobileUpdate'); //anjali

    //$router->get('AutoCrActions', ['middleware' => 'throttle:1,5','uses' => 'api\PayoutController@AutoCrActions',]);
});