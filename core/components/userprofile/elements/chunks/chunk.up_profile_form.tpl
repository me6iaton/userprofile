<form action="" method="post" class="form-horizontal well" id="userprofile-form" enctype="multipart/form-data">
    <div class="header">
        <small>[[%up_profile_header]]</small>
    </div>

    <div class="form-group avatar">
        <label class="col-sm-2 control-label">[[%up_profile_avatar]]</label>
        <div class="col-sm-10">
            <img src="[[+avatar]]" id="profile-user-photo" data-gravatar="[[+gravatar]]?s=100" width="100" />
            <a href="#" id="userprofile-user-photo-remove" [[+photo:is=``:then=`style="display:none;"`]]">
            [[%up_profile_avatar_remove]]
            <i class="glyphicon glyphicon-remove"></i>
            </a>
            <p class="help-block">[[%up_profile_avatar_desc]]</p>
            <input type="hidden" name="photo" value="[[+photo]]" />
            <input type="file" name="newphoto" id="profile-photo" />
        </div>
    </div>
    <div class="form-group">
        <label class="col-sm-2 control-label">[[%up_profile_username]]<sup class="red">*</sup></label>
        <div class="col-sm-10">
            <input type="text" name="username" value="[[+username]]" placeholder="[[%up_profile_username]]"  class="form-control" />
            <p class="help-block message">[[+error_username]]</p>
            <p class="help-block desc">[[%up_profile_username_desc]]</p>
        </div>
    </div>
    <div class="form-group">
        <label class="col-sm-2 control-label">[[%up_profile_fullname]]<sup class="red">*</sup></label>
        <div class="col-sm-10">
            <input type="text" name="fullname" value="[[+fullname]]" placeholder="[[%up_profile_fullname]]" class="form-control" />
            <p class="help-block message">[[+error_fullname]]</p>
            <p class="help-block desc">[[%up_profile_fullname_desc]]</p>
        </div>
    </div>
    <div class="form-group">
        <label class="col-sm-2 control-label">[[%up_profile_email]]<sup class="red">*</sup></label>
        <div class="col-sm-10">
            <input type="text" name="email" value="[[+email]]" placeholder="[[%up_profile_email]]" class="form-control" />
            <p class="help-block message">[[+error_email]]</p>
            <p class="help-block desc">[[%up_profile_email_desc]]</p>
        </div>
    </div>
    <div class="form-group">
        <label class="col-sm-2 control-label">[[%up_profile_password]]</label>
        <div class="col-sm-10">
            <input type="password" name="specifiedpassword" value="" placeholder="********" class="form-control" />
            <p class="help-block message">[[+error_specifiedpassword]]</p>
            <p class="help-block desc">[[%up_profile_specifiedpassword_desc]]</p>
            <input type="password" name="confirmpassword" value="" placeholder="********" class="form-control" />
            <p class="help-block message">[[+error_confirmpassword]]</p>
            <p class="help-block desc">[[%up_profile_confirmpassword_desc]]</p>
        </div>
    </div>
    <div class="form-group">
        <label class="col-sm-2 control-label">[[%ha.providers_available]]</label>
        <div class="col-sm-10">
            [[+providers]]
        </div>
    </div>
    <hr/>
    <div class="form-group">
        <div class="col-sm-offset-2 col-sm-10">
            <button type="submit" class="btn btn-primary">[[%up_profile_save]]</button>
            &nbsp;&nbsp;&nbsp;&nbsp;
            <a class="btn btn-danger" href="[[~[[*id]]]]?action=auth/logout">[[%up_profile_logout]]</a>
        </div>
    </div>
</form>