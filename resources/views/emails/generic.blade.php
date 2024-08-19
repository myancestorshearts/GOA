



@extends('emails/layout/page')

@section('page_content')


<table width="100%" border="0" cellpadding="0" cellspacing="0" class="container590" style="padding: 0 20px;">
    <tbody class="nana">    
        <tr>
            <td>
                <div style="font-size: 30px;line-height:45px;font-weight: 600;">
                    {{$title}}
                </div>
                <br/>
            </td>
        </tr>
        <tr>
            <td>
                <div style="font-size: 14px;font-family: Open Sans, sans-serif, serif;">    
                    {!!$content!!}
                </div>
            </td>
        </tr>

        <tr>
            <td>
                <hr style="border-color: #ddd;color: #ddd;background: transparent;border-width: 0 0px 0px 0;margin-top: 0px;border-bottom: 0px solid" />
            </td>
        </tr>
        @if (isset($action_link) && isset($action_text))
            <tr>
                <td>
                    <a href="{{$action_link}}" target="_blank" style="padding: 12px 12px;background-color:#313d44;font-family: Helvetica, Arial, sans-serif;font-size: 14px;color: #ffffff;text-decoration: none;font-weight:bold;display: block;width: 140px;margin: auto;text-align: center;border-radius: 10px;">
                        {{$action_text}}
                    </a>
                </td>
            </tr>
        @endif
    </tbody>
</table>
@stop
