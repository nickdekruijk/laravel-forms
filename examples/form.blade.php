{!! Form::open(null,['handler' => 'log|mail', 'mail_to' => 'user@domain.com', 'mail_subject' => 'Form submission']) !!}
    @if (session('form-status'))
        <div class="alert alert-ok">
            <h4>Thanks you</h4>
        </div>
    @else
        {!! Form::errors($errors, 'Sorry, niet alles was juist ingevuld:') !!}
        <label>Name{!! Form::text('name', null, null, 'required') !!}</label>
        <label>Department{!! Form::select('department', ['' => 'Make your choice', 'Sales', 'Support']) !!}</label>
        <label>Email{!! Form::email('email', null, null, 'required|email:rfc,dns') !!}</label>
        <label>Website{!! Form::text('website', null, null, 'required|active_url') !!}</label>
        <label>Question{!! Form::textarea('question', null, ['rows'=>5], 'required') !!}</label>
        <label>Attachment (pdf, doc or docx, max. 10 MB){!! Form::file('attachment', null, null, 'required|file|mimes:pdf,doc,docx|max:10240') !!}</label>
        <label>{!! Form::button('Submit', ['class'=>'button primary']) !!}</label>
    @endif
{!! Form::close() !!}
