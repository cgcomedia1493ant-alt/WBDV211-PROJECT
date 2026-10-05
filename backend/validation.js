
const validation = new JustValidate('#signup');

validation
    .addField('#name', [
        {
            rule: 'required',
            errorMessage: 'Name is required'
        }
    ])
    .addField('#email', [
        {
            rule: 'required',
            errorMessage: 'Email is required'
        },
        {
            rule: 'email',
            errorMessage: 'Please enter a valid email'
        }
    ])
    .addField('#password', [
        {
            rule: 'required',
            errorMessage: 'Password is required'
        },
        {
            rule: 'minLength',
            value: 8,
            errorMessage: 'Password must be at least 8 characters'
        }
    ])
    .addField('#password_confirmation', [
        {
            rule: 'required',
            errorMessage: 'Please confirm your password'
        },
        {
            validator: (value, fields) =>
                value === fields['#password'].elem.value,
            errorMessage: 'Passwords do not match'
        }
    ])
    .onSuccess((event) => {
        event.target.submit();
    });
