<table cellspacing="0" border="0" cellpadding="0" width="100%" bgcolor="#f2f3f8" style="@import url(https://fonts.googleapis.com/css?family=Rubik:300,400,500,700|Open+Sans:300,400,600,700); font-family: 'Open Sans', sans-serif;">
    <tr>
      <td>
        <table style="background-color: #f2f3f8; max-width:670px;  margin:0 auto;" width="100%" border="0" align="center" cellpadding="0" cellspacing="0">
          <tr>
            <td style="height:80px;">&nbsp;</td>
          </tr>
          <tr>
            <td style="text-align:center;">
              <a href="iconnhrm.io" title="logo" target="_blank">

              </a>
            </td>
          </tr>
          <tr>
            <td style="height:20px;">&nbsp;</td>
          </tr>
          <tr>
            <td>
              <table width="95%" border="0" align="center" cellpadding="0" cellspacing="0" style="max-width:670px;background:#fff; border-radius:3px; text-align:center;-webkit-box-shadow:0 6px 18px 0 rgba(0,0,0,.06);-moz-box-shadow:0 6px 18px 0 rgba(0,0,0,.06);box-shadow:0 6px 18px 0 rgba(0,0,0,.06);">
                <tr>
                  <td style="height:40px;">&nbsp;</td>
                </tr>
                <tr>
                  <td style="padding:0 55px;">
                    <img width="60" src="{{url('/images/get-started.png')}}" title="logo" alt="logo">
                    <!--                     get the name from the data here -->
                    <p style="color:#474747; margin-top: 25px"><strong>Hi, {{ $data['firstName'] }}!</strong></p>
                    <h4 style="color:#474747; font-weight:300; margin-top: 24px;font-size:22px;font-family:'Rubik',sans-serif;">Welcome to <img width="150" src="{{url('/images/icon-hrm-logo.png')}}" title="logo" alt="logo"> </h4>
            
                    <p style="text-align:justify; color:#474747;  font-size:15px;line-height:24px; width:98%;">
                      {{$data['emailBody']}}
                    </p>
                    <a href="{{ $data['link'] }}" style="background:#86C129;text-decoration:none !important; font-weight:500; margin-top:35px; color:#FFFFFF; font-size:14px;padding:10px 24px;display:inline-block;">
                    {{$data['buttonText']}} Acknowledge Form</a>
                    <br><br>

                    <br><br>
                    <p style="color:#474747; line-height:24px; text-align:left;">Cheers, <br>The ICONN HRM Team<br></p>
                  </td>
                </tr>
                <tr>
                  <td style="height:40px;">&nbsp;</td>
                </tr>
              </table>
            </td>
          <tr>
            <td style="height:20px;">&nbsp;</td>
          </tr>
          <tr>
            <td style="text-align:center;">
              <p style="font-size:14px; color:rgba(69, 80, 86, 0.7411764705882353); line-height:18px; margin:0 0 0;">&copy; <strong>iconnhrm.io</strong></p>
            </td>
          </tr>
          <tr>
            <td style="height:80px;">&nbsp;</td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
