jQuery( document ).ready(function() {
	
	window.CustomizeAvatar = jQuery.extend({}, {
		
		CustomizeAvatarController : null,
		
		init : function() {
			
			var self = this;
			
			CustomizeAvatarController = self;

			self.getCustomize();
			self.refactorMoodleForm();
			
			self.charactersLeftLastState = "characters-left";
			
			self.bindEvents();
		},
		
		bindEvents : function() {
			
			var self = this;
			
			jQuery("#id_gender_male,#id_gender_female").change(function() {
				self.defineGenderAvatarImage();
			});
			
			var interval;
			jQuery("#id_message").focus(function() {
				interval = window.setInterval(self.charactersLeftCount, 100);
			});
			
			jQuery("#id_message").blur(function() {
				clearInterval(interval);
			});
			
			jQuery("#id_save_customize").click(function() {
				self.saveCustomize();
			});
		},
		
		defaultValuesForm : function() {
			
			var self = this;
			
			self.defineGender("male");
			self.defineAvatarRadioImage("male", 1)
			self.defineGenderAvatarImage();
			self.defineNotifications(true, true, true);
			self.charactersLeftCount();
		},
		
		refactorMoodleForm : function() {
			var formCustomizeAvatar = jQuery("#id_customize_block_avatar_displayinfo").parent();
			var formId = jQuery(formCustomizeAvatar).attr("id");
			var formClass = jQuery(formCustomizeAvatar).attr("class");
			
			jQuery(formCustomizeAvatar).replaceWith('<div class="avatar block-avatar-customize-form">' + jQuery(formCustomizeAvatar).html() + "</div>");
			
			jQuery(".avatar.block-avatar-customize-form").attr('id', formId);
			jQuery(".avatar.block-avatar-customize-form").addClass(formClass);
		},
		
		defineGender : function(gender) {
			
			if(gender == "male") {
				jQuery("#id_gender_male").prop('checked', true);
				jQuery("#id_gender_female").prop('checked', false);
			} else {
				jQuery("#id_gender_female").prop('checked', true);
				jQuery("#id_gender_male").prop('checked', false);
			}
			
		},
		
		defineGenderAvatarImage : function() {
			if(jQuery("#id_gender_male").prop("checked")) {
				jQuery(".avatar.block-avatar-images.male").parent().parent().fadeIn('slow');
				jQuery(".avatar.block-avatar-images.female").parent().parent().hide();
			} else {
				jQuery(".avatar.block-avatar-images.female").parent().parent().fadeIn('slow');
				jQuery(".avatar.block-avatar-images.male").parent().parent().hide();
			}
		},
		
		defineAvatarRadioImage : function(gender, id) {
			
			jQuery(".avatar.block-avatar-images").parent().prev().prop("checked", false);
			jQuery("#avatar-block-avatar-images-"+gender+id).parent().prev().prop("checked", true);
			jQuery(".avatar.block-avatar-images").fadeIn('slow');
		},
		
		defineNotifications : function(newContentSent, newActivitiesSent, pendingActivities) {
			
			if(newContentSent) {
				jQuery("#id_new_contents_sent_notification").prop("checked", true);
			} else {
				jQuery("#id_new_contents_sent_notification").prop("checked", false);
			}
			
			if(newActivitiesSent) {
				jQuery("#id_new_activities_sent_notification").prop("checked", true);
			} else {
				jQuery("#id_new_activities_sent_notification").prop("checked", false);
			}
			
			if(pendingActivities) {
				jQuery("#id_pending_activities_notification").prop("checked", true);
			} else {
				jQuery("#id_pending_activities_notification").prop("checked", false);
			}
			
		},
		
		charactersLeftCount : function() {
			
			/* necessary due to setInterval */
			var self = CustomizeAvatarController;
			
			var elem = jQuery("#id_message");
			
			var maxCharacters = 300;
			var message = jQuery(elem).val();
			var leftCharacters = maxCharacters - message.length;
			
			if(message.length > maxCharacters) {
				jQuery(elem).val(message.substring(0, maxCharacters));
			}
			
			
			/* update count */
			leftCharacters = maxCharacters - jQuery(elem).val().length;
			
			switch (leftCharacters) {
				case 0:
					if(self.charactersLeftLastState != "no-character-left") {
						jQuery(".avatar.block-avatar-characters-left").text(self.getString("no-character-left"));
						
						self.charactersLeftLastState = "no-character-left";
					}
					break;
				
				case 1:
					if(self.charactersLeftLastState != "character-left") {
						jQuery(".avatar.block-avatar-characters-left").text(self.getString("character-left"));
						
						self.charactersLeftLastState = "character-left";
					}
					break;
	
				default:
					if(self.charactersLeftLastState != "characters-left") {
						jQuery(".avatar.block-avatar-characters-left").text(self.getString("characters-left"));
						self.charactersLeftLastState = "characters-left";
					}
					break;
			}
			
			if(leftCharacters > 0) {
				jQuery(".avatar.block-avatar-characters-left-count").text(leftCharacters);
			} else {
				jQuery(".avatar.block-avatar-characters-left-count").text("");
			}
			
		},
		
		getCustomize : function() {
			var self = this;
			
			var courseid = jQuery("#avatar_courseid").val();
			
			jQuery.ajax({
				type: 'GET',
				data: {
					action: 'get',
					courseid : courseid
				},
				url: 'action_customize_avatar.php',
				dataType: 'json',
				success: function(json) {
					if(json != false && json != null && json !== undefined) {
						
						self.defineGender(json['gender']);
						self.defineAvatarRadioImage(json['gender'], json['avatarid'])
						self.defineGenderAvatarImage();
						
						var new_contents_sent = json['new_contents_sent'] == 0 ? false : true;
						var new_activities_sent = json['new_activities_sent'] == 0 ? false : true;
						var pending_activities = json['pending_activities'] == 0 ? false : true;
						
						self.defineNotifications(new_contents_sent, new_activities_sent, pending_activities);
						
						jQuery("#id_message").val(json['message']);
					} else {
						self.defaultValuesForm();
					}
					self.charactersLeftCount();
				},
				error: function() {
					self.defaultValuesForm();
				}
			});
			
		},
		
		saveCustomize : function() {
			
			var self = this;
			
			var courseid = jQuery("#avatar_courseid").val();
			var avatarid = jQuery(".avatar.block-avatar-radio-images:checked").val();
			
			var new_contents_sent = jQuery("#id_new_contents_sent_notification").prop("checked");
			var new_activities_sent = jQuery("#id_new_activities_sent_notification").prop("checked");
			var pending_activities = jQuery("#id_pending_activities_notification").prop("checked");
			var message = jQuery("#id_message").val();
			
			jQuery.ajax({
				
				type: 'GET',
				data: {
					action: 'save',
					courseid : courseid,
					avatarid : avatarid,
					new_contents_sent : new_contents_sent,
					new_activities_sent : new_activities_sent,
					pending_activities : pending_activities,
					message : message
				},
				url: 'action_customize_avatar.php',
				dataType: 'html',
				beforeSend: function() {
					jQuery("#id_avatar_customize_error").hide();
					jQuery("#id_avatar_customize_loading").show();
				},
				success: function(html) {
					if(html != "true") {
						self.saveError();
					} else {
						/*jQuery(location).attr('href', M.cfg['wwwroot']+'/course/view.php?id='+courseid);*/
						self.synthesize(courseid, avatarid, message);
					}
				},
				error: function() {
					self.saveError();
				},
				complete: function() {
					jQuery("#id_avatar_customize_loading").hide();
				}
			});
			
		},
		
		synthesize : function(courseid, avatarid, message) {
			
			var self = this;
			
			var speaker = "cid";
			var parameter;
			
			if(courseid > 1) {
				parameter = 2; /*course*/
			} else {
				parameter = 3; /*home*/
			}
			
			if(avatarid < 10) {
				speaker = "cid";
			} else if(avatarid < 20) {
				speaker = "lis";
			}
			
			jQuery.ajax({
				
				type: 'POST',
				data: {
					userid : 0,
					courseid : courseid,
					parameter: parameter,
					phrase : message,
					speaker : speaker
				},
				url: M.cfg['wwwroot'] + '/blocks/avatar/synthesizer.php',
				dataType: 'html',
				success: function(html) {
					if(html != "0") {
						self.saveError();
					} else {
						jQuery(location).attr('href', M.cfg['wwwroot']+'/course/view.php?id='+courseid);
					}
				},
				error: function() {
					self.saveError();
				}
			});
			
		},
		
		saveError : function() {
			jQuery("#id_avatar_customize_error").text("Desculpe, aconteceu algo errado.");
			jQuery("#id_avatar_customize_error").fadeIn('slow');
		},
		
		getString : function(str) {
			return M.str['block_avatar'][str];
		}
		
	}).init(); 

});








