
$(function()
{
	window.checkChangeAlert = function(elm,oldCost,oldState){
		var nowCost = $("input[type=text][name=cost]",elm).val();
		var nowState = $("input[type=radio][name=state]:checked",elm).val();

		var dCost = 0;

		if( oldState != nowState ) //ステータスが変更されている場合
		{
			switch( nowState ) //現在のステータスで分岐
			{
				case '2': //認証
				{
					dCost = nowCost;
				}
				break;
				case '1': //未認証
				{
					if( 0 != oldState )
						{ dCost = -1*oldCost; }
				}
				break;
			}
		}
		else //ステータスが変更されていない場合
		{
			if( 2 == oldState ) //ステータスが認証の場合
			{
				if( oldCost != nowCost ) //報酬に変化があった場合。
					dCost = nowCost - oldCost;
			}
		}
		if( dCost != 0 )
		{
			if( !confirm( "該当のアフィリエイターに対し、"+Math.abs(dCost)+"円の報酬が"+((dCost>=0)?'加算':'減算')+"されます。" ) )
			{
				return false;
			}
		}

		return true;
	}
});