(function($) {
	'use strict';

	var BoosterOnboarding = {
		modal: null,
		currentGoal: null,
		currentBlueprint: null,
		currentMode: 'goals',

		init: function() {
			this.modal = $( '#booster-onboarding-modal' );
			this.bindEvents();
			this.setupAccessibility();
			this.loadModePreference();
			this.updateAppliedBadges();
		},

		bindEvents: function() {
			var self = this;

			$( document ).on(
				'click',
				'#launch-onboarding-modal',
				function() {
					self.showModal();
				}
			);

			$( document ).on(
				'click',
				'.booster-modal-close, .booster-modal-overlay',
				function() {
					self.closeModal();
				}
			);

			$( document ).on(
				'click',
				'.segment-button',
				function() {
					var mode = $( this ).data( 'mode' );
					self.switchMode( mode );
				}
			);

			$( document ).on(
				'click',
				'.booster-goal-tile',
				function() {
					var goalId = $( this ).data( 'goal' );
					self.selectGoal( goalId );
				}
			);

			$( document ).on(
				'click',
				'.booster-blueprint-tile',
				function() {
					var blueprintId = $( this ).data( 'blueprint' );
					self.selectBlueprint( blueprintId );
				}
			);

			$( document ).on(
				'click',
				'.back-button',
				function() {
					self.showChooseScreen();
				}
			);

			$( document ).on(
				'click',
				'.cancel-button',
				function() {
					self.showChooseScreen();
				}
			);

			$( document ).on(
				'click',
				'.apply-button',
				function() {
					if (self.currentGoal) {
						self.applyGoal();
					} else if (self.currentBlueprint) {
						self.applyBlueprint();
					}
				}
			);

			$( document ).on(
				'click',
				'.close-button',
				function() {
					self.closeModal();
				}
			);

			$( document ).on(
				'click',
				'.pick-another-button',
				function() {
					self.showChooseScreen();
				}
			);

			$( document ).on(
				'click',
				'.undo-goal',
				function() {
					var goalId = $( this ).data( 'goal' );
					self.undoGoal( goalId );
				}
			);

			$( document ).on(
				'keydown',
				function(e) {
					if (e.keyCode === 27 && self.modal.is( ':visible' )) {
						self.closeModal();
					}
				}
			);
		},

		setupAccessibility: function() {
			var self = this;

			this.modal.on(
				'keydown',
				function(e) {
					if (e.keyCode === 9) {
						var focusableElements = self.modal.find( 'button:visible, [href]:visible, input:visible, select:visible, textarea:visible, [tabindex]:not([tabindex="-1"]):visible' );
						var firstElement      = focusableElements.first();
						var lastElement       = focusableElements.last();

						if (e.shiftKey) {
							if ($( document.activeElement ).is( firstElement )) {
								e.preventDefault();
								lastElement.focus();
							}
						} else {
							if ($( document.activeElement ).is( lastElement )) {
								e.preventDefault();
								firstElement.focus();
							}
						}
					}
				}
			);
		},

		loadModePreference: function() {
			if (typeof localStorage !== 'undefined') {
				var savedMode = localStorage.getItem( 'booster_onboarding_mode' );
				if (savedMode && (savedMode === 'goals' || savedMode === 'blueprints')) {
					this.currentMode = savedMode;
				}
			}
		},

		saveModePreference: function(mode) {
			if (typeof localStorage !== 'undefined') {
				localStorage.setItem( 'booster_onboarding_mode', mode );
			}
		},

		switchMode: function(mode) {
			this.currentMode = mode;
			this.saveModePreference( mode );

			$( '.segment-button' ).removeClass( 'active' ).attr( 'aria-selected', 'false' );
			$( '.segment-button[data-mode="' + mode + '"]' ).addClass( 'active' ).attr( 'aria-selected', 'true' );

			if (mode === 'goals') {
				$( '.booster-goals-screen' ).addClass( 'active' );
				$( '.booster-blueprints-screen' ).removeClass( 'active' );
			} else {
				$( '.booster-blueprints-screen' ).addClass( 'active' );
				$( '.booster-goals-screen' ).removeClass( 'active' );
			}

			this.logEvent( 'mode_switch', { mode: mode } );
		},

		updateAppliedBadges: function() {
			if (typeof boosterOnboarding.appliedGoals !== 'undefined') {
				boosterOnboarding.appliedGoals.forEach(
					function(goalId) {
						$( '.booster-goal-tile[data-goal="' + goalId + '"] .applied-badge' ).show();
					}
				);
			}
		},

		updateProgressIndicator: function(step) {
			$( '.progress-step' ).removeClass( 'active completed' );

			if (step === 'choose') {
				$( '.progress-step[data-step="choose"]' ).addClass( 'active' );
			} else if (step === 'review') {
				$( '.progress-step[data-step="choose"]' ).addClass( 'completed' );
				$( '.progress-step[data-step="review"]' ).addClass( 'active' );
			} else if (step === 'complete') {
				$( '.progress-step[data-step="choose"]' ).addClass( 'completed' );
				$( '.progress-step[data-step="review"]' ).addClass( 'completed' );
				$( '.progress-step[data-step="complete"]' ).addClass( 'active' );
			}
		},

		showModal: function() {
			this.modal.show();
			this.showChooseScreen();
			this.switchMode( this.currentMode );

			this.logEvent( 'modal_open', {} );

			setTimeout(
				function() {
					if (this.currentMode === 'goals') {
						$( '.booster-goal-tile' ).first().focus();
					} else {
						$( '.booster-blueprint-tile' ).first().focus();
					}
				}.bind( this ),
				100
			);
		},

		closeModal: function() {
			this.modal.hide();
			this.currentGoal = null;
			this.currentBlueprint = null;
			this.logEvent( 'modal_close', {} );
		},

		showChooseScreen: function() {
			this.updateProgressIndicator( 'choose' );

			if (this.currentMode === 'goals') {
				this.modal.find( '.booster-goals-screen' ).addClass( 'active' );
				this.modal.find( '.booster-blueprints-screen' ).removeClass( 'active' );
			} else {
				this.modal.find( '.booster-blueprints-screen' ).addClass( 'active' );
				this.modal.find( '.booster-goals-screen' ).removeClass( 'active' );
			}

			this.modal.find( '.booster-review-screen, .booster-success-screen, .booster-loading-screen' ).removeClass( 'active' );
		},

		showReviewScreen: function() {
			this.updateProgressIndicator( 'review' );
			this.modal.find( '.booster-review-screen' ).addClass( 'active' );
			this.modal.find( '.booster-goals-screen, .booster-blueprints-screen, .booster-success-screen, .booster-loading-screen' ).removeClass( 'active' );
		},

		showSuccessScreen: function() {
			this.updateProgressIndicator( 'complete' );
			this.modal.find( '.booster-success-screen' ).addClass( 'active' );
			this.modal.find( '.booster-goals-screen, .booster-blueprints-screen, .booster-review-screen, .booster-loading-screen' ).removeClass( 'active' );
		},

		showLoadingScreen: function() {
			this.modal.find( '.booster-loading-screen' ).addClass( 'active' );
			this.modal.find( '.booster-goals-screen, .booster-blueprints-screen, .booster-review-screen, .booster-success-screen' ).removeClass( 'active' );
		},

		selectGoal: function(goalId) {
			if ( ! boosterOnboarding.goals[goalId]) {
				return;
			}

			this.currentGoal = goalId;
			this.currentBlueprint = null;
			var goal         = boosterOnboarding.goals[goalId];

			$( '#review-goal-title' ).text( goal.title );

			var modulesList = $( '#modules-list' );
			modulesList.empty();
			goal.modules.forEach(
				function(module) {
					var moduleName = module.name;
					modulesList.append( '<li>' + moduleName + '</li>' );
				}
			);

			var settingsList = $( '#settings-list' );
			settingsList.empty();
			goal.modules.forEach(
				function(module) {
					Object.keys( module.settings ).forEach(
						function(key) {
							if (key === 'create_demo_draft') {
								settingsList.append( '<li>' + boosterOnboarding.strings.create_demo_draft + '</li>' );
							} else if (key === 'add_one_extra') {
								settingsList.append( '<li>' + boosterOnboarding.strings.add_one_extra + '</li>' );
							} else {
								var value = module.settings[key];
								if (typeof value === 'object') {
									value = JSON.stringify( value );
								}
								settingsList.append( '<li>' + key + ': ' + value + '</li>' );
							}
						}
					);
				}
			);

			this.logEvent( 'goal_select', { goal_id: goalId } );
			this.showReviewScreen();
		},

		selectBlueprint: function(blueprintId) {
			if ( ! boosterOnboarding.blueprints[blueprintId]) {
				return;
			}

			this.currentBlueprint = blueprintId;
			this.currentGoal = null;
			var blueprint = boosterOnboarding.blueprints[blueprintId];

			$( '#review-goal-title' ).text( blueprint.title );

			var modulesList = $( '#modules-list' );
			modulesList.empty();
			modulesList.append( '<li>' + blueprint.description + '</li>' );

			var settingsList = $( '#settings-list' );
			settingsList.empty();
			settingsList.append( '<li><strong>Includes ' + blueprint.goal_keys.length + ' goals:</strong></li>' );
			blueprint.goal_keys.forEach(
				function(goalKey) {
					if (boosterOnboarding.goals[goalKey]) {
						settingsList.append( '<li>• ' + boosterOnboarding.goals[goalKey].title + '</li>' );
					}
				}
			);

			this.logEvent( 'blueprint_select', { blueprint_id: blueprintId } );
			this.showReviewScreen();
		},

		applyGoal: function() {
			if ( ! this.currentGoal) {
				return;
			}

			var self           = this;
			var createSnapshot = $( '#create-snapshot' ).is( ':checked' );

			this.showLoadingScreen();
			$( '#loading-message' ).text( boosterOnboarding.strings.applying );

			$.ajax(
				{
					url: boosterOnboarding.ajaxUrl,
					type: 'POST',
					data: {
						action: 'booster_apply_goal',
						goal_id: this.currentGoal,
						create_snapshot: createSnapshot,
						nonce: boosterOnboarding.nonce
					},
					success: function(response) {
						if (response.success) {
							self.showGoalSuccessScreen( response.data );
							self.logEvent( 'goal_apply_success', { goal_id: self.currentGoal } );
						} else {
							alert( response.data.message || boosterOnboarding.strings.error );
							self.showReviewScreen();
							self.logEvent( 'goal_apply_error', { goal_id: self.currentGoal, error: response.data.message } );
						}
					},
					error: function() {
						alert( boosterOnboarding.strings.error );
						self.showReviewScreen();
						self.logEvent( 'goal_apply_error', { goal_id: self.currentGoal, error: 'ajax_error' } );
					}
				}
			);
		},

		applyBlueprint: function() {
			if ( ! this.currentBlueprint) {
				return;
			}

			var self           = this;
			var createSnapshot = $( '#create-snapshot' ).is( ':checked' );

			this.showLoadingScreen();
			$( '#loading-message' ).text( boosterOnboarding.strings.applying );

			$.ajax(
				{
					url: boosterOnboarding.ajaxUrl,
					type: 'POST',
					data: {
						action: 'booster_apply_blueprint',
						blueprint_id: this.currentBlueprint,
						create_snapshot: createSnapshot,
						nonce: boosterOnboarding.nonce
					},
					success: function(response) {
						if (response.success) {
							self.showBlueprintSuccessScreen( response.data );
							self.logEvent( 'blueprint_apply_success', { blueprint_id: self.currentBlueprint } );
						} else {
							alert( response.data.message || boosterOnboarding.strings.error );
							self.showReviewScreen();
							self.logEvent( 'blueprint_apply_error', { blueprint_id: self.currentBlueprint, error: response.data.message } );
						}
					},
					error: function() {
						alert( boosterOnboarding.strings.error );
						self.showReviewScreen();
						self.logEvent( 'blueprint_apply_error', { blueprint_id: self.currentBlueprint, error: 'ajax_error' } );
					}
				}
			);
		},

		showGoalSuccessScreen: function(data) {
			$( '#success-message' ).text( data.message );

			$( '#next-steps-container' ).hide();
			$( '#pro-note-container' ).hide();
			$( '.primary-cta-button' ).hide();

			if (data.next_step_text && data.next_step_link) {
				$( '#primary-cta-text' ).text( data.next_step_text );
				$( '.primary-cta-button' ).attr( 'href', data.next_step_link ).show();
			}

			this.showSuccessScreen();

			setTimeout(
				function() {
					window.location.reload();
				},
				3000
			);
		},

		showBlueprintSuccessScreen: function(data) {
			$( '#success-message' ).text( data.message );

			if (data.next_steps && data.next_steps.length > 0) {
				var nextStepsList = $( '#next-steps-list' );
				nextStepsList.empty();
				data.next_steps.forEach(
					function(step) {
						nextStepsList.append( '<li><a href="' + step.href + '">' + step.label + '</a></li>' );
					}
				);
				$( '#next-steps-container' ).show();
			} else {
				$( '#next-steps-container' ).hide();
			}

			if (data.pro_note) {
				$( '#pro-note-link' ).text( data.pro_note.label ).attr( 'href', data.pro_note.href );
				$( '#pro-note-container' ).show();
			} else {
				$( '#pro-note-container' ).hide();
			}

			if (data.primary_cta) {
				$( '#primary-cta-text' ).text( data.primary_cta.label );
				$( '.primary-cta-button' ).attr( 'href', data.primary_cta.href ).show();
			} else {
				$( '.primary-cta-button' ).hide();
			}

			this.showSuccessScreen();

			setTimeout(
				function() {
					window.location.reload();
				},
				3000
			);
		},

		undoGoal: function(goalId) {
			var self = this;

			if ( ! confirm( boosterOnboarding.strings.confirmUndo )) {
				return;
			}

			var button       = $( '.undo-goal[data-goal="' + goalId + '"]' );
			var originalText = button.text();
			button.text( boosterOnboarding.strings.undoing ).prop( 'disabled', true );

			$.ajax(
				{
					url: boosterOnboarding.ajaxUrl,
					type: 'POST',
					data: {
						action: 'booster_undo_goal',
						goal_id: goalId,
						nonce: boosterOnboarding.nonce
					},
					success: function(response) {
						if (response.success) {
							alert( response.data.message );
							self.logEvent( 'goal_undo_success', { goal_id: goalId } );
							window.location.reload();
						} else {
							alert( response.data.message || boosterOnboarding.strings.error );
							button.text( originalText ).prop( 'disabled', false );
							self.logEvent( 'goal_undo_error', { goal_id: goalId, error: response.data.message } );
						}
					},
					error: function() {
						alert( boosterOnboarding.strings.error );
						button.text( originalText ).prop( 'disabled', false );
						self.logEvent( 'goal_undo_error', { goal_id: goalId, error: 'ajax_error' } );
					}
				}
			);
		},

		logEvent: function(eventType, eventData) {
			$.ajax(
				{
					url: boosterOnboarding.ajaxUrl,
					type: 'POST',
					data: {
						action: 'booster_log_onboarding_event',
						event_type: eventType,
						event_data: eventData,
						nonce: boosterOnboarding.nonce
					}
				}
			);
		}
	};

	$( document ).ready(
		function() {
			BoosterOnboarding.init();
		}
	);

	window.BoosterOnboarding = BoosterOnboarding;

})( jQuery );

jQuery( document ).ready(
	function($) {
		if (window.location.hash === '#launch-onboarding-modal') {
			if (typeof BoosterOnboarding !== 'undefined') {
				BoosterOnboarding.showModal();
			}
		}

		const urlParams = new URLSearchParams( window.location.search );
		if (urlParams.get( 'modal' ) === 'onboarding') {
			if (typeof BoosterOnboarding !== 'undefined') {
				BoosterOnboarding.showModal();
			}
		}
	}
);
