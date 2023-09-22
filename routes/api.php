<?php

use App\Http\Controllers\API\Admin\CrudAdminController;
use App\Http\Controllers\API\Admin\CrudDemandeController;
use App\Http\Controllers\API\Admin\CrudInvitationController;
use App\Http\Controllers\API\Admin\CrudPojectController;
use App\Http\Controllers\API\Admin\CrudStudentController;
use App\Http\Controllers\API\Admin\CrudStudentProjectsController;
use App\Http\Controllers\API\Admin\CrudUsersAdminController;
use App\Http\Controllers\API\AdministrateurController;
use App\Http\Controllers\API\AppInfoConfigurationController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\DemandeEmailController;
use App\Http\Controllers\API\DemandeInscriptionController;
use App\Http\Controllers\API\EmailConfigurationController;
use App\Http\Controllers\API\EmailTemplateController;
use App\Http\Controllers\API\EtudiantController;
use App\Http\Controllers\API\ForgotController;
use App\Http\Controllers\API\Guest\EventProjectOrTaskController;
use App\Http\Controllers\API\Guest\MeetingController;
use App\Http\Controllers\API\Guest\ReminderController;
use App\Http\Controllers\API\MembersController;
use App\Http\Controllers\API\NotificationController;
use App\Http\Controllers\API\ProjectThreadsController;
use App\Http\Controllers\API\ProjetController;
use App\Http\Controllers\API\TaskController;
use App\Http\Controllers\API\UniversiteDomaineController;
use App\Http\Controllers\API\User\ActivityLogController;
use App\Http\Controllers\API\User\GestionDemandeInscriptionController;
use App\Http\Controllers\API\UsersController;
use App\Http\Controllers\API\WhmcsConfigurationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\UserController;
use App\Http\Controllers\VerifyEmailController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
//

Route::group(['middleware' => 'guest:api'], function () {
    Route::post('forgot', [ForgotController::class, 'forgot']);
    Route::post('reset', [ForgotController::class, 'reset']);
    Route::get('/email/verify/{user}', [VerifyEmailController::class, 'verify'])->name('verificationapi.verify')->middleware(['signed']);
    Route::get('/email/resend', [VerifyEmailController::class, 'resend']);
    Route::post('/login',  [AuthController::class, 'login']);
    Route::get("/details_application", [AppInfoConfigurationController::class, "AppConfiguration"]);
    Route::post('/inscription', [DemandeInscriptionController::class, 'store']);
    Route::post('/invitation/accept', [CrudInvitationController::class, 'acceptInvitation']);
    Route::get("/invitation/{token}", [CrudInvitationController::class, "index"]);
//    Route::post('register', [RegisterController::class, 'register']);
//
//    Route::post('password/email', [ForgotPasswordController::class, 'sendResetLinkEmail']);
//    Route::post('password/reset', [ResetPasswordController::class, 'reset']);
//
//    Route::get('/email/verify/{id}/{hash}', [VerifyEmailController::class, '__invoke'])
//        ->middleware(['signed', 'throttle:6,1'])
//        ->name('verification.verify');
//
//// Resend link to verify email
//    Route::post('/email/verify/resend', function (Request $request) {
//        $request->user()->sendEmailVerificationNotification();
//        return back()->with('message', 'Verification link sent!');
//    })->middleware(['auth:api', 'throttle:6,1'])->name('verification.send');
//
//    Route::post('oauth/{driver}', [OAuthController::class, 'redirect']);
//    Route::get('oauth/{driver}/callback', [OAuthController::class, 'handleCallback'])->name('oauth.callback');
});
Route::group(['middleware' => ['auth:api','IsAdmin']], function () {
    Route::group(['prefix' => 'admin'], function () {




        Route::post('/store', [CrudAdminController::class, 'store']);
        Route::group(['prefix' => 'account'], function () {
            Route::post('/email/update/{id}', [CrudAdminController::class, 'updateEmail']);
            Route::post('/password/update/{id}', [CrudAdminController::class, 'updatePassword']);
            Route::post('/update', [CrudAdminController::class, 'edit']);

        });

        Route::group(['prefix' => 'demandes'], function () {
            Route::get('list', [CrudDemandeController::class, 'index']);
            Route::post('add',  [CrudDemandeController::class, 'store']);
            Route::post('mass/delete',  [CrudDemandeController::class, 'deleteMultiple']);
            Route::delete('delete/{id}',  [CrudDemandeController::class, 'delete']);
            Route::post('/update/{id}', [CrudDemandeController::class, 'update']);
            Route::get('{id}', [CrudDemandeController::class, 'show']);


        });
         Route::group(['prefix' => 'users'], function () {
             Route::group(['prefix' => 'projects'], function () {
                 Route::get('list', [CrudStudentProjectsController::class, 'index']);
                 Route::post('mass/delete',  [CrudStudentProjectsController::class, 'deleteMultipleProjects']);
                 Route::delete('delete/{id}',  [CrudStudentProjectsController::class, 'deleteProject']);

                 Route::post('update/{id}',  [CrudStudentProjectsController::class, 'updateStudentProject']);

                 Route::group(['prefix' => 'members'], function () {

                     Route::get('pending/{id}',  [CrudStudentProjectsController::class, 'pendingInvitationsProject']);
                     Route::get('{id}',  [CrudStudentProjectsController::class, 'getTeamMembersProject']);
                 });

                 Route::group(['prefix' => 'tasks'], function () {
                     Route::get('stats/{id}',  [TaskController::class, 'tasksOverTime']);
                     Route::get('summary/{id}',  [TaskController::class, 'tasksSummary']);
                     Route::get('list/{id}',  [CrudStudentProjectsController::class, 'listTasks']);
                     Route::delete('delete/{id}',  [CrudStudentProjectsController::class, 'deleteTask']);

                     Route::group(['prefix' => 'comments'], function () {

                         Route::delete('delete/{id}',  [CrudStudentProjectsController::class, 'deleteTaskComment']);
                     });
                 });
                 Route::get('{id}', [CrudStudentProjectsController::class, 'show']);

             });
             Route::group(['prefix' => 'invites'], function () {
                 Route::post('add',  [CrudInvitationController::class, 'store']);
                 Route::get('list', [CrudInvitationController::class, 'invitedList']);
                 Route::post('mass/delete',  [CrudInvitationController::class, 'deleteMultipleInvited']);
                 Route::delete('delete/{id}',  [CrudInvitationController::class, 'deleteInvited']);
                 Route::post('/email/update/{id}', [CrudInvitationController::class, 'updateInvitedEmail']);
                 Route::post('/password/update/{id}', [CrudInvitationController::class, 'updateInvitedPassword']);
                 Route::post('/update/{id}', [CrudInvitationController::class, 'updateInvited']);
                 Route::group(['prefix' => 'projects'], function () {
                     Route::get('{id}', [CrudInvitationController::class, 'getInvitedProjects']);


                 });
                 Route::get('{id}', [CrudInvitationController::class, 'show']);

             });
             Route::group(['prefix' => 'admins'], function () {
                 Route::get('list', [CrudUsersAdminController::class, 'index']);
                 Route::post('add',  [CrudUsersAdminController::class, 'store']);
                 Route::post('mass/delete',  [CrudUsersAdminController::class, 'deleteMultipleAdmins']);
                 Route::delete('delete/{id}',  [CrudUsersAdminController::class, 'deleteAdmin']);
                 Route::post('/email/update/{id}', [CrudUsersAdminController::class, 'updateAdminEmail']);
                 Route::post('/password/update/{id}', [CrudUsersAdminController::class, 'updateAdminPassword']);
                 Route::post('/update/{id}', [CrudUsersAdminController::class, 'updateAdmin']);
                 Route::get('{id}', [CrudUsersAdminController::class, 'show']);
             });
             Route::group(['prefix' => 'students'], function () {

                 Route::get('list', [CrudStudentController::class, 'index']);
                 Route::post('mass/delete',  [CrudStudentController::class, 'deleteMultipleStudents']);
                 Route::delete('delete/{id}',  [CrudStudentController::class, 'deleteStudent']);
                 Route::post('/email/update/{id}', [CrudStudentController::class, 'updateStudentEmail']);
                 Route::post('/password/update/{id}', [CrudStudentController::class, 'updateStudentPassword']);
                 Route::post('/update/{id}', [CrudStudentController::class, 'updateStudent']);
                 Route::group(['prefix' => 'projects'], function () {
                     Route::post('add/{id}',  [CrudStudentProjectsController::class, 'store']);
                     Route::get('{id}', [CrudStudentProjectsController::class, 'getStudentProjects']);
                 });
                 Route::get('{id}', [CrudStudentController::class, 'show']);
             });
         });
    });


});
Route::group(['middleware' => 'auth:api','IsBanned'], function () {
    Route::post('verify_token', [UserController::class, 'accountdetails']);
    Route::post('logout', [LoginController::class, 'logout']);
    Route::get('user', [UserController::class, 'current']);
    Route::get('details', [UserController::class, 'accountdetails']);
    Route::group(['prefix' => 'fich'], function () {
        Route::post('/email/update/{id}', [GestionDemandeInscriptionController::class, 'updateEmail']);
        Route::post('/password/update/{id}', [GestionDemandeInscriptionController::class, 'updatePassword']);
        Route::post('/update', [GestionDemandeInscriptionController::class, 'edit']);
        Route::get('',  [GestionDemandeInscriptionController::class, 'index']);
    });
    Route::group(['prefix' => 'project'], function () {
        Route::post('add',  [ProjetController::class, 'store']);
        Route::get('list',  [ProjetController::class, 'index']);
        Route::get('all',  [ProjetController::class, 'ExpandedList']);
        Route::get('connection',  [ProjetController::class, 'index']);
        Route::group(['prefix' => 'meetings'], function () {
            Route::post('add',  [MeetingController::class, 'store']);
            Route::get('all',  [MeetingController::class, 'getMeetingsForUser']);
            Route::get('fromtoday',  [MeetingController::class, 'getMeetingsForUserToDay']);
            Route::get('list/{slug}',  [MeetingController::class, 'projectMeetings']);
            Route::get('counted/{count}/{slug}',  [MeetingController::class, 'getMeetingsForProjectLimited']);
            Route::delete('delete/{id}',  [MeetingController::class, 'delete']);
        });
        Route::post('update/{id}',  [ProjetController::class, 'updateProject']);
        Route::group(['prefix' => 'task'], function () {
            Route::group(['prefix' => 'reminder'], function () {
                Route::post('add',  [ReminderController::class, 'store']);
                Route::get('all',  [ReminderController::class, 'getRemindersForUser']);

                Route::get('list/{slug}',  [ReminderController::class, 'getRemindersForProject']);
                    Route::get('counted/{count}/{slug}',  [ReminderController::class, 'getRemindersForProjectLimited']);
                Route::delete('delete/{id}',  [ReminderController::class, 'delete']);
                Route::post('update/{id}',  [ReminderController::class, 'update']);
                Route::get('/{id}',  [ReminderController::class, 'show']);

            });
            Route::group(['prefix' => 'event'], function () {
                Route::post('add',  [EventProjectOrTaskController::class, 'store']);
                Route::get('fromtoday',  [EventProjectOrTaskController::class, 'FromToDayEvents']);
                Route::delete('delete/{id}',  [EventProjectOrTaskController::class, 'delete']);
                Route::post('update/{id}',  [EventProjectOrTaskController::class, 'update']);
                Route::get('/{id}',  [EventProjectOrTaskController::class, 'show']);

            });
            Route::get('all',  [TaskController::class, 'allTasks']);
            Route::get('today/all',  [TaskController::class, 'allTasksToDay']);
            Route::get('list/{slug}',  [TaskController::class, 'index']);
            Route::post('add',  [TaskController::class, 'store']);
            Route::delete('delete/{id}',  [TaskController::class, 'deleteTask']);
            Route::post('update/status/{id0}',  [TaskController::class, 'changeTaskStatus']);
            Route::post('update/{id}',  [TaskController::class, 'updateTask']);
            Route::get('byslug/{slug}',  [TaskController::class, 'getTaskBySlug']);
            Route::group(['prefix' => 'comment'], function () {
                Route::post('add',  [TaskController::class, 'addTaskComment']);
                Route::post('update/{id}',  [TaskController::class, 'updateTaskComment']);
                Route::delete('delete/{id}',  [TaskController::class, 'deleteTaskComment']);
            });
        });
        Route::group(['prefix' => 'members'], function () {
            Route::post('invite/{slug}',  [MembersController::class, 'inviteMember']);
            Route::get('pending/{slug}',  [MembersController::class, 'pendingInvitations']);
            Route::get('{slug}',  [MembersController::class, 'getTeamMembers']);
        });
                Route::group(['prefix' => 'conversation'], function () {
            Route::get('list',  [ProjectThreadsController::class, 'getUserThreads']);
            Route::post('start',  [ProjectThreadsController::class, 'store']);
            Route::post('reply',  [ProjectThreadsController::class, 'ReplyMessage']);
                Route::post('update/{id}',  [ProjectThreadsController::class, 'updateSubject']);
            Route::get('favourite/{id}',  [ProjectThreadsController::class, 'starThread']);
            Route::get('unfavourite/{id}',  [ProjectThreadsController::class, 'unstarThread']);
            Route::group(['prefix' => 'members'], function () {
                Route::post('add/{id}',  [ProjectThreadsController::class, 'addThreadParticipant']);
                Route::post('remove/{id}',  [ProjectThreadsController::class, 'removeThreadParticipant']);
                Route::get('quit/{id}',  [ProjectThreadsController::class, 'quitThread']);
                Route::get('markasread/{id}',  [ProjectThreadsController::class, 'markAsRead']);
            });
        });
        Route::get('byslug/{slug}',  [ProjetController::class, 'show']);

    });
    Route::group(['prefix' => 'account'], function () {
        Route::get('notifications',  [NotificationController::class, 'index']);
        Route::get('today/activity',  [ActivityLogController::class, 'ToDayActivity']);
        Route::get('connection',  [ProjetController::class, 'getTeamMembersAndOwners']);
        Route::get('teams',  [ProjetController::class, 'getUserTeams']);
        Route::get('invitations',  [ProjetController::class, 'getTeamInvites']);
        Route::post('accept/invitation',  [ProjetController::class, 'acceptInvitation']);
        Route::post('reject/invitation',  [ProjetController::class, 'denyInvitation']);

    });





//    Route::patch('settings/password', [PasswordController::class, 'update']);
    Route::group([
        'prefix' => 'client'
    ], function ()
    {

        Route::get('login',  [EtudiantController::class, 'ClientLoginDirectAdmin']);



        Route::post('profile/edit',  [EtudiantController::class, 'edit']);
//        Route::delete('/delete/{id}',  [DemandeInscriptionController::class, 'destroy']);
        Route::post('profile/modifier/password',  [EtudiantController::class, 'updatePassword']);
//        Route::delete('/email/delete/{id}',  [DemandeEmailController::class, 'destroy']);
        Route::get('/{id}',  [DemandeInscriptionController::class, 'show']);
        Route::group(['prefix' => 'projets'], function () {
            Route::get('tasks',  [TaskController::class, 'TasksToDo']);

            Route::get('invitations',  [ProjetController::class, 'invitations']);
            Route::get('/checkdomaine/{domaine}',  [UniversiteDomaineController::class, 'checkdomaine']);
            Route::get('domaines-list',  [UniversiteDomaineController::class, 'index']);
            Route::get('produits',  [EtudiantController::class, 'GetClientProduct']);
            Route::get('tickets',  [EtudiantController::class, 'GetTickets']);
            Route::get('departments',  [EtudiantController::class, 'GetDepartments']);
            Route::post('ticket/attachments/{id}',  [EtudiantController::class, 'GetTicketAttachment']);
            Route::post('ticket/reply',  [EtudiantController::class, 'AddTicketReply']);
            Route::post('ajouter/ticket',  [EtudiantController::class, 'OpenTicket']);
            Route::get('update/ticket/{id}',  [EtudiantController::class, 'UpdateTicket']);
            Route::get('ticket/{id}',  [EtudiantController::class, 'GetTicket']);
            Route::get('',  [ProjetController::class, 'index']);
//            Route::get('invite',  [InviteController::class, 'invite'])->name('invite');
//                Route::post('invite', [InviteController::class, 'process'])->name('process');
//// {token} is a required parameter that will be exposed to us in the controller method
//            Route::get('accept/{token}', [InviteController::class, 'accept'])->name('accept');
        });
        Route::group(['prefix' => 'projet'], function () {
            Route::post('demarrer/conversation',  [ProjectThreadsController::class, 'store']);
            Route::post('repondre/conversation',  [ProjectThreadsController::class, 'ReplyMessage']);
            Route::post('supprimer/membre/conversation',  [ProjectThreadsController::class, 'RemoveThreadParticipant']);
            Route::post('quitter/conversation',  [ProjectThreadsController::class, 'QuitThread']);
            Route::post('ajouter/membre/conversation',  [ProjectThreadsController::class, 'AddThreadParticipant']);
            Route::post('marquer_comme_lu/conversation',  [ProjectThreadsController::class, 'MarkAsRead']);
            Route::post('epingler/conversation',  [ProjectThreadsController::class, 'Star']);
            Route::post('detacher/conversation',  [ProjectThreadsController::class, 'UnStar']);
            Route::get('messages',  [ProjectThreadsController::class, 'index'])->name('message');
            Route::get('users/conversation',  [ProjectThreadsController::class, 'UsersArray']);
            Route::post('/update/tasks', [TaskController::class, 'UpdateAll']);
            Route::get('/tasks', [TaskController::class, 'index']);
            Route::get('/task',  [TaskController::class, 'show']);
            Route::post('ajouter/task', [TaskController::class, 'store']);
            Route::post('ajouter/subtask', [TaskController::class, 'storeSub']);
            Route::post('edit/task', [TaskController::class, 'update']);
            Route::post('update_task_status', [TaskController::class, 'updateStatus']);
            Route::post('edit/commentaire', [TaskController::class, 'updateCommentaire']);
            Route::post('ajouter/commentaire', [TaskController::class, 'storeCommentaire']);
            Route::delete('delete/commentaire/{id}', [TaskController::class, 'destroyCommentaire']);
            Route::post('assigntome/task', [TaskController::class, 'AssignToMe']);
            Route::delete('delete/task/{id}', [TaskController::class, 'destroy']);
            Route::post('accepter-invitation',  [ProjetController::class, 'accept']);
            Route::post('refuser-invitation',  [ProjetController::class, 'reject']);
            Route::post('update',  [ProjetController::class, 'edit']);
            Route::post('invite-membre',  [ProjetController::class, 'InviteMember']);
            Route::post('delete-invitation',  [ProjetController::class, 'DeleteInvitation']);
            Route::post('delete-membre',  [ProjetController::class, 'DeleteMember']);
            Route::get('url-login',  [EtudiantController::class, 'ClientDirectAdminUrllogin']);
            Route::post('ajouter',  [ProjetController::class, 'create']);
            Route::get('/{slug}',  [ProjetController::class, 'show']);


        });

        Route::group(['prefix' => 'domain'], function () {
            Route::get('',  [EtudiantController::class, 'ClientDirectAdminDomain']);
            Route::group(['prefix' => 'subdomains'], function () {
                Route::get('',  [EtudiantController::class, 'ClientDirectAdminSubDomain']);
                Route::post('delete/subdomain',  [EtudiantController::class, 'ClientDirectAdminDeleteSubDomain']);
                Route::post('ajouter/subdomain',  [EtudiantController::class, 'ClientDirectAdminAddSubDomain']);
            });
            Route::group(['prefix' => 'pointeurs'], function () {
                Route::get('',  [EtudiantController::class, 'ClientDirectAdminDomainPointers']);
                Route::post('delete/pointeur',  [EtudiantController::class, 'ClientDirectAdminDomainDeletePointer']);
                Route::post('ajouter/pointeur',  [EtudiantController::class, 'ClientDirectAdminAddPointer']);
            });

            Route::post('update_domain',  [EtudiantController::class, 'ClientDirectAdminModifyAccount']);
            Route::group(['prefix' => 'dns'], function () {
                Route::get('',  [EtudiantController::class, 'ClientDirectAdminZoneDNS']);
                Route::post('ajouter/record',  [EtudiantController::class, 'ClientDirectAdminAddZoneDNS']);
                Route::post('edit/record',  [EtudiantController::class, 'ClientDirectAdminEditZoneDNS']);
                Route::post('delete/record',  [EtudiantController::class, 'ClientDirectAdminDeleteZoneDNS']);
            });
            Route::group(['prefix' => 'ssl'], function () {
                Route::get('',  [EtudiantController::class, 'ClientDirectAdminSSL']);
                Route::post('edit/record',  [EtudiantController::class, 'ClientDirectAdminEditSSL']);
            });
            Route::group(['prefix' => 'emails'], function () {
                Route::get('',  [EtudiantController::class, 'ClientDirectAdminDomainEmails']);
                Route::post('log/email',  [EtudiantController::class, 'ClientDirectAdminDomainLogEmail']);
                Route::post('ajouter/email',  [EtudiantController::class, 'ClientDirectAdminDomainCreateEmail']);
                Route::post('edit/email',  [EtudiantController::class, 'ClientDirectAdminDomainEditEmail']);
                Route::post('delete/email',  [EtudiantController::class, 'ClientDirectAdminDomainDeleteEmail']);
            });
            Route::group(['prefix' => 'service'], function () {
                Route::get('',  [EtudiantController::class, 'ClientDirectRedirect']);

            });


            Route::group(['prefix' => 'database'], function () {
                Route::get('',  [EtudiantController::class, 'ClientDirectAdminDataBaseList']);
                Route::post('list/users',  [EtudiantController::class, 'ClientDirectAdminDataBaseUsersList']);
                Route::post('list/allusers',  [EtudiantController::class, 'ClientDirectAdminDataBaseListUsers']);
                Route::post('ajouter/record',  [EtudiantController::class, 'ClientDirectAdminAddDataBase']);
                Route::post('ajouter/user',  [EtudiantController::class, 'ClientDirectAdminAddUserDataBase']);
                Route::post('ajouter/existing_user',  [EtudiantController::class, 'ClientDirectAdminAddExistingUserDataBase']);
                Route::post('delete/record',  [EtudiantController::class, 'ClientDirectAdminDeleteDataBase']);
                Route::post('delete/user',  [EtudiantController::class, 'ClientDirectAdminDeleteUserDataBase']);
                Route::post('update/user_privileges',  [EtudiantController::class, 'ClientDirectAdminUpdateUserPRIVILEGESDataBase']);
                Route::post('update/user',  [EtudiantController::class, 'ClientDirectAdminUpdatePasswordUserDataBase']);
                Route::post('get/user/privileges',  [EtudiantController::class, 'ClientDirectAdminUserPRIVILEGESDataBase']);
            });
            Route::group(['prefix' => 'ftp'], function () {
                Route::get('',  [EtudiantController::class, 'ClientDirectAdminUserFTP']);
                Route::post('ajouter/record',  [EtudiantController::class, 'ClientDirectAdminUserCreateFTPAccount']);
                Route::post('delete/record',  [EtudiantController::class, 'ClientDirectAdminUserDeleteFTPAccount']);

            });
        });


    });
});









Route::group(['middleware' => ['auth:api','IsAdmin']], function () {

    Route::get('liste_demandes', [DemandeInscriptionController::class, 'index']);
    Route::get("configuration", [EmailConfigurationController::class, "Configuration"]);
    Route::post("setconfiguration", [EmailConfigurationController::class, "createConfiguration"]);
    Route::get("whconfiguration", [WhmcsConfigurationController::class, "Configuration"]);
    Route::post("setwhconfiguration", [WhmcsConfigurationController::class, "createConfiguration"]);
    Route::get("appconfiguration", [AppInfoConfigurationController::class, "Configuration"]);
    Route::post("setappconfiguration", [AppInfoConfigurationController::class, "createConfiguration"]);
    Route::post("setdomainconfiguration", [UniversiteDomaineController::class, "store"]);
    Route::delete('/domaine/delete/{id}',  [UniversiteDomaineController::class, 'destroy']);
    Route::group([
        'prefix' => 'demande'
    ], function () {
        Route::post('/accept/{id}',  [DemandeInscriptionController::class, 'AcceptDemande']);
        Route::post('/reject/{id}',  [DemandeInscriptionController::class, 'RejectDemande']);
        Route::post('/edit/{id}',  [DemandeInscriptionController::class, 'edit']);
        Route::post('/edit_univ/{id}',  [DemandeInscriptionController::class, 'EditUniversityInfo']);
        Route::delete('/delete/{id}',  [DemandeInscriptionController::class, 'destroy']);
        Route::post('/envoyer_email/{id}',  [DemandeInscriptionController::class, 'sendEmail']);
        Route::delete('/email/delete/{id}',  [DemandeEmailController::class, 'destroy']);
        Route::get('/{id}',  [DemandeInscriptionController::class, 'show']);

    });
    Route::group([
        'prefix' => 'users'
    ], function () {
        Route::get('',  [UsersController::class, 'index']);
        Route::get('invited/{id}',  [UsersController::class, 'ShowInvited']);
        Route::get('invitees',  [UsersController::class, 'invited']);
        Route::get('/projects',  [UsersController::class, 'ListProjects']);
        Route::post('/accept/project/{id}',  [DemandeInscriptionController::class, 'AccpetProject']);
        Route::post('/suspend/project/{id}',  [DemandeInscriptionController::class, 'SuspendProject']);
        Route::post('/unsuspend/project/{id}',  [DemandeInscriptionController::class, 'UnSuspendProject']);
        Route::post('/terminate/project/{id}',  [DemandeInscriptionController::class, 'TerminateProject']);
        Route::post('/update/project/{id}',  [DemandeInscriptionController::class, 'UpdateProject']);
        Route::get('/project/{id}',  [UsersController::class, 'ShowProject']);
        Route::get('/countprojects/{id}',  [UsersController::class, 'ShowInvitedProject']);
        Route::post('/suspend/user/{id}',  [DemandeInscriptionController::class, 'ban']);
        Route::post('/unsuspend/user/{id}',  [DemandeInscriptionController::class, 'unban']);
        Route::delete('/delete/invited/{id}',  [UsersController::class, 'destroy']);
//
//        Route::post('/edit/{id}',  [DemandeInscriptionController::class, 'edit']);
//        Route::delete('/delete/{id}',  [DemandeInscriptionController::class, 'destroy']);
//        Route::post('/envoyer_email/{id}',  [DemandeInscriptionController::class, 'sendEmail']);
//        Route::delete('/email/delete/{id}',  [DemandeEmailController::class, 'destroy']);
//        Route::get('/{id}',  [DemandeInscriptionController::class, 'show']);

    });
    Route::group([
        'prefix' => 'email_templates'
    ], function () {
        Route::get('',  [EmailTemplateController::class, 'index']);
        Route::get('/{id}',  [EmailTemplateController::class, 'show']);
        Route::post('/store',  [EmailTemplateController::class, 'store']);
        Route::delete('/delete/{id}',  [EmailTemplateController::class, 'destroy']);
    });
    Route::group([
        'prefix' => 'administrateurs'
    ], function () {

        Route::delete('/delete/{id}',  [AdministrateurController::class, 'destroy']);
        Route::post('/edit/{id}',  [AdministrateurController::class, 'edit']);
        Route::post('/ajouter',  [AdministrateurController::class, 'store']);
        Route::get('/{id}',  [AdministrateurController::class, 'show']);
        Route::get('',  [AdministrateurController::class, 'index']);
    });
//    Route::patch('settings/profile', [ProfileController::class, 'update']);
//    Route::patch('settings/password', [PasswordController::class, 'update']);
});
//Route::fallback(function (){
//    abort(404, 'API resource not found');
//});
Route::fallback(function(){
    return response()->json([
        'message' => 'lien introuvable. Si l\'erreur persiste, contacter riadh.benali9@gmail.com'], 404);
});
//Route::middleware('auth:api')->group(function(){
//    Route::get('/user', [AuthController::class,'authenticatedUserDetails']);
//});
