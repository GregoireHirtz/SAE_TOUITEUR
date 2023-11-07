window.addEventListener('load', () => {
	let inputs = document.querySelectorAll('.input > input');
	inputs.forEach(input => {
		input.addEventListener('input', () => {
			input.setAttribute('value', input.value);
		});
	});

	let password_inputs = document.querySelectorAll('.input.password');
	password_inputs.forEach(input => {
		input.querySelector('img').addEventListener('click', (event) => {
			let el_input = input.querySelector('input');
			if (event.target.src.includes('slash')) {
				el_input.setAttribute('type', 'text');
				event.target.title = 'Cacher le mot de passe';
				event.target.src = 'src/vue/images/eye.svg';
			} else {
				el_input.setAttribute('type', 'password');
				event.target.title = 'Afficher le mot de passe';
				event.target.src = 'src/vue/images/eye-slash.svg';
			}
		});
	});
});


function switchLogMethod(form, switchTo) {
	form.style.display = 'none';
	if (switchTo === 'connect')
		form.previousElementSibling.style.display = 'flex';
	else
		form.nextElementSibling.style.display = 'flex';
}