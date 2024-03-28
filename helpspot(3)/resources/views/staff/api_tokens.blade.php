<div stye="font-size:13px;">
    <p>
        {!! lg_admin_users_api_explanation !!}
    </p>
</div>
<table cellpadding="0" cellspacing="0" border="0" class="tablebody " width="100%" id="rsgroup_1">
    <tbody>
        @if($tokens->count() == 0)
            <tr>
                <td colspan="5"><div class="table-no-results">{{ lg_admin_users_api_no_tokens }}</div></td>
            </tr>
        @else
            <tr class="tableheaders" valign="bottom">
                <td scope="col" id="1_table_header_id">{{ lg_admin_users_api_label_id }}</td>
                <td scope="col" id="2_table_header_id">{{ lg_admin_users_api_label_token_name }}</td>
                <td scope="col" id="3_table_header_id">{{ lg_admin_users_api_label_created_at }}</td>
                <td scope="col" id="4_table_header_id">{{ lg_admin_users_api_label_last_used }}</td>
                <td scope="col" id="4_table_header_id"></td>
            </tr>
        @endif

        @foreach($tokens as $token)
            <tr class="tablerowoff">
                <td class="tcell">{{ $token->id }}</td>
                <td class="tcell">{{ $token->name }}</td>
                <td class="tcell">{{ $token->created_at }}</td>
                <td class="tcell">{{ $token->last_used_at ?? lg_admin_users_api_label_unused }}</td>
                <td class="tcell" style="white-space: nowrap;">
                    <a style="padding: 0 6px;" href="{{ route('tokens.destroy', ['token' => $token->id]) }}" title="revoke token" class="revoke-token">
                        <img src="{{ static_url() }}/static/img5/trash-solid.svg" alt="revoke token" style="width: 14px;">
                    </a>
                </td>
            </tr>
        @endforeach

        <tr>
            <td class="tablefooter" colspan="5"></td>
        </tr>
    </tbody>
</table>

<div>
    <div class="button-bar inline-form">
        <div>
            <input tabindex="105" type="text" name="token-name" id="token-name" class="focus-shift inline-left @if($errors->has('token-name')) error @endif" value="" placeholder="{{ lg_admin_users_api_token_name }}">
        </div>
        <div>
            <a
                tabindex="106"
                id="issue-new-token"
                class="btn accent inline-right"
                data-action="{{ route('tokens.store') }}"
            >{{ lg_admin_users_api_issue_new }}</a>
        </div>
    </div>
    <div id="token-errors" style="color: #db504a; padding: .4rem 0;">
        @if($errors->any())
            @foreach($errors->all() as $error)
                <p>{{ $error->first() }}</p>
            @endforeach
        @endif
    </div>
</div>

<script type="text/javascript">
    $jq(document).ready(function() {
        var isProcessing = false;

        var handlerFn = function(e) {
            e.preventDefault();

            if (isProcessing) {
                return false;
            }

            $jq('#token-name').removeClass('error')
            $jq('#token-errors').empty()
            isProcessing = true;

            $jq.post($jq(e.currentTarget).data('action'), {
                'token-name': $jq('#token-name').val(),
                'xPerson': {{ $xPerson }}
            })
                .done(function(data) {
                    var res = '<pre style="font-size: 16px; margin-bottom: .6rem;padding: .4rem;background: #dcdcdc; white-space: pre-wrap; word-wrap: break-word;"><code>'+data.plainTextToken+'</code></pre>';
                    res += '<p style="font-weight: bold;">This token will only be shown once!</p>';

                    hs_confirm(res, function() {
                        window.location.reload();
                    }, {title: '{{ lg_admin_users_api_token_new }}', showCancelButton:false})

                })
                .fail(function(xhr) {
                    isProcessing = false;

                    if (xhr.status == 422) {
                        var errors = JSON.parse(xhr.responseText)
                        var errorBox = $jq('#token-errors')
                        $jq('#token-name').addClass('error')

                        for (error in errors.errors) {
                            errorBox.append("<p>"+errors.errors[error][0]+"</p>")
                        }
                    }
                });

            return false;
        };

        // Handle enter/click of issue token button
        $jq('#issue-new-token')
            .click(handlerFn)
            .keyup(function(e) {
                e.preventDefault();
                if (e.keyCode === 13) {
                    handlerFn(e)
                }
                return false;
            });

        // Handle enter on token name field
        $jq('#token-name').keydown(function(e) {
            if (e.keyCode === 13) {
                e.stopImmediatePropagation();
                e.preventDefault();
                $jq('#issue-new-token').trigger("click");
                return false;
            }
        });

        var isRevoking = false;
        $jq('.revoke-token').click(function(e) {
           e.preventDefault();

           if (isRevoking) {
               return false;
           }

           hs_confirm('<p>{{ lg_admin_users_api_token_revoke_confirm }}</p>', function() {
               $jq.post($jq(e.currentTarget).attr('href'), {
                   '_method': 'DELETE'
               }).always(function() {
                   isRevoking = false;
                   window.location.reload();
               });
           })

           return false;
        });
    });
</script>
