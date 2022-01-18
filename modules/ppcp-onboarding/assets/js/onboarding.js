// Onboarding.
const ppcp_onboarding = {
	BUTTON_SELECTOR: '[data-paypal-onboard-button]',
	PAYPAL_JS_ID: 'ppcp-onboarding-paypal-js',
	_timeout: false,

	init: function() {
		document.addEventListener('DOMContentLoaded', this.reload);
	},

	reload: function() {
		const buttons = document.querySelectorAll(ppcp_onboarding.BUTTON_SELECTOR);

		if (0 === buttons.length) {
			return;
		}

		// Add event listeners to buttons preventing link clicking if PayPal init failed.
		buttons.forEach(
			(element) => {
				if (element.hasAttribute('data-ppcp-button-initialized')) {
					return;
				}

				element.addEventListener(
					'click',
					(e) => {
						if (!element.hasAttribute('data-ppcp-button-initialized') || 'undefined' === typeof window.PAYPAL) {
							e.preventDefault();
						}
					}
				);
			}
		);

		// Clear any previous PayPal scripts.
		[ppcp_onboarding.PAYPAL_JS_ID, 'signup-js', 'biz-js'].forEach(
			(scriptID) => {
				const scriptTag = document.getElementById(scriptID);

				if (scriptTag) {
					scriptTag.parentNode.removeChild(scriptTag);
				}

				if ('undefined' !== typeof window.PAYPAL) {
					delete window.PAYPAL;
				}
			}
		);

		// Load PayPal scripts.
		const paypalScriptTag = document.createElement('script');
		paypalScriptTag.id = ppcp_onboarding.PAYPAL_JS_ID;
		paypalScriptTag.src = PayPalCommerceGatewayOnboarding.paypal_js_url;
		document.body.append(paypalScriptTag);

		if (ppcp_onboarding._timeout) {
			clearTimeout(ppcp_onboarding._timeout);
		}

		ppcp_onboarding._timeout = setTimeout(
			() => {
				buttons.forEach((element) => { element.setAttribute('data-ppcp-button-initialized', 'true'); });

				if ('undefined' !== window.PAYPAL.apps.Signup) {
					window.PAYPAL.apps.Signup.render();
				}
			},
			1000
		);
	},

	loginSeller: function(env, authCode, sharedId) {
		fetch(
			PayPalCommerceGatewayOnboarding.endpoint,
			{
				method: 'POST',
				headers: {
					'content-type': 'application/json'
				},
				body: JSON.stringify(
					{
						authCode: authCode,
						sharedId: sharedId,
						nonce: PayPalCommerceGatewayOnboarding.nonce,
						env: env
					}
				)
			}
		);
	},


};

function ppcp_onboarding_sandboxCallback(...args) {
	return ppcp_onboarding.loginSeller('sandbox', ...args);
}

function ppcp_onboarding_productionCallback(...args) {
	return ppcp_onboarding.loginSeller('production', ...args);
}

/**
 * Toggles the credential input fields.
 *
 * @param forProduction
 */
const credentialToggle = (forProduction) => {

	const sandboxClassSelectors = [
		'#field-ppcp_disconnect_sandbox',
		'#field-merchant_email_sandbox',
		'#field-merchant_id_sandbox',
		'#field-client_id_sandbox',
		'#field-client_secret_sandbox',
	];
	const productionClassSelectors = [
		'#field-ppcp_disconnect_production',
		'#field-merchant_email_production',
		'#field-merchant_id_production',
		'#field-client_id_production',
		'#field-client_secret_production',
	];

	const selectors = forProduction ? productionClassSelectors : sandboxClassSelectors;
	document.querySelectorAll(selectors.join()).forEach(
		(element) => {element.classList.toggle('show')}
	)
};

/**
 * Toggles the visibility of the sandbox/production input fields.
 *
 * @param showProduction
 */
const toggleSandboxProduction = (showProduction) => {
	const productionDisplaySelectors = [
		'#field-credentials_production_heading',
	];
	const productionClassSelectors = [

		'#field-ppcp_disconnect_production',
		'#field-merchant_email_production',
		'#field-merchant_id_production',
		'#field-client_id_production',
		'#field-client_secret_production',
	];
	const sandboxDisplaySelectors = [
		'#field-credentials_sandbox_heading',
	];
	const sandboxClassSelectors = [
		'#field-ppcp_disconnect_sandbox',
		'#field-merchant_email_sandbox',
		'#field-merchant_id_sandbox',
		'#field-client_id_sandbox',
		'#field-client_secret_sandbox',
	];

	if (showProduction) {
		document.querySelectorAll(productionDisplaySelectors.join()).forEach(
			(element) => {element.style.display = ''}
		);
		document.querySelectorAll(sandboxDisplaySelectors.join()).forEach(
			(element) => {element.style.display = 'none'}
		);
		document.querySelectorAll(productionClassSelectors.join()).forEach(
			(element) => {element.classList.remove('hide')}
		);
		document.querySelectorAll(sandboxClassSelectors.join()).forEach(
			(element) => {
				element.classList.remove('show');
				element.classList.add('hide');
			}
		);
		return;
	}
	document.querySelectorAll(productionDisplaySelectors.join()).forEach(
		(element) => {element.style.display = 'none'}
	);
	document.querySelectorAll(sandboxDisplaySelectors.join()).forEach(
		(element) => {element.style.display = ''}
	);

	document.querySelectorAll(sandboxClassSelectors.join()).forEach(
		(element) => {element.classList.remove('hide')}
	);
	document.querySelectorAll(productionClassSelectors.join()).forEach(
		(element) => {
			element.classList.remove('show');
			element.classList.add('hide');
		}
	)
};

const updateOptionsState = () => {
    const cardsChk = document.querySelector('#ppcp-onboarding-accept-cards');

    document.querySelectorAll('#ppcp-onboarding-dcc-options input').forEach(input => {
        input.disabled = !cardsChk.checked;
    });

    const basicRb = document.querySelector('#ppcp-onboarding-dcc-basic');

    const isExpress = !cardsChk.checked || basicRb.checked;

    const expressButtonSelectors = [
        '#field-ppcp_onboarding_production_express',
        '#field-ppcp_onboarding_sandbox_express',
    ];
    const ppcpButtonSelectors = [
        '#field-ppcp_onboarding_production_ppcp',
        '#field-ppcp_onboarding_sandbox_ppcp',
    ];

    document.querySelectorAll(expressButtonSelectors.join()).forEach(
        element => element.style.display = isExpress ? '' : 'none'
    );
    document.querySelectorAll(ppcpButtonSelectors.join()).forEach(
        element => element.style.display = !isExpress ? '' : 'none'
    );
};

const disconnect = (event) => {
	event.preventDefault();
	const fields = event.target.classList.contains('production') ? [
		'#field-merchant_email_production input',
		'#field-merchant_id_production input',
		'#field-client_id_production input',
		'#field-client_secret_production input',
	] : [
		'#field-merchant_email_sandbox input',
		'#field-merchant_id_sandbox input',
		'#field-client_id_sandbox input',
		'#field-client_secret_sandbox input',
	];

	document.querySelectorAll(fields.join()).forEach(
		(element) => {
			element.value = '';
		}
	);
	document.querySelector('.woocommerce-save-button').click();
};

// Prevent the message about unsaved checkbox/radiobutton when reloading the page.
// (WC listens for changes on all inputs and sets dirty flag until form submission)
const preventDirtyCheckboxPropagation = event => {
    event.preventDefault();
    event.stopPropagation();

    const value = event.target.checked;
    setTimeout( () => {
            event.target.checked = value;
        }, 1
    );
};

(() => {
	const sandboxSwitchElement = document.querySelector('#ppcp-sandbox_on');
	if (sandboxSwitchElement) {
		toggleSandboxProduction(! sandboxSwitchElement.checked);
	}

	document.querySelectorAll('.ppcp-disconnect').forEach(
		(button) => {
			button.addEventListener(
				'click',
				disconnect
			);
		}
	);

	if (sandboxSwitchElement) {
		sandboxSwitchElement.addEventListener(
			'click',
			(event) => {
				const value = event.target.checked;

				toggleSandboxProduction( ! value );

                preventDirtyCheckboxPropagation(event);
			}
		);
	}

    document.querySelectorAll('.ppcp-onboarding-options input').forEach(
        (element) => {
            element.addEventListener('click', event => {
                updateOptionsState();

                preventDirtyCheckboxPropagation(event);
            });
        }
    );

    updateOptionsState();

    document.querySelectorAll('#field-toggle_manual_input button').forEach(
        (button) => {
            button.addEventListener(
                'click',
                (event) => {
                    event.preventDefault();

                    const isProduction = ! sandboxSwitchElement.checked;
                    toggleSandboxProduction(isProduction);
                    credentialToggle(isProduction);
                }
            )
        }
    );

	// Onboarding buttons.
	ppcp_onboarding.init();
})();
