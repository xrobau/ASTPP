-------------------------------------------------------------------------------------
-- ASTPP - Open Source VoIP Billing Solution
--
-- Copyright (C) 2016 iNextrix Technologies Pvt. Ltd.
-- Samir Doshi <samir.doshi@inextrix.com>
-- ASTPP Version 3.0 and above
-- License https://www.gnu.org/licenses/agpl-3.0.html
--
-- This program is free software: you can redistribute it and/or modify
-- it under the terms of the GNU Affero General Public License as
-- published by the Free Software Foundation, either version 3 of the
-- License, or (at your option) any later version.
-- 
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU Affero General Public License for more details.
-- 
-- You should have received a copy of the GNU Affero General Public License
-- along with this program.  If not, see <http://www.gnu.org/licenses/>.
--------------------------------------------------------------------------------------

orig_destination_number = params:getHeader("Caller-Destination-Number")

if (orig_destination_number == nil) then
    return;
end

Logger.info("[Dialplan] Original Dialed number : "..orig_destination_number)
destination_number = remap_dest_number(orig_destination_number)
Logger.info("[Dialplan] Remapped  Dialed number : "..destination_number)

--Check if dialed number is calling card access number
if (config['cc_access_numbers'] ~= '') then 
local cc = split(config['cc_access_numbers'],",")      
    for cc_key,cc_value in pairs(cc) do
	if (destination_number == cc_value) then
    	    generate_cc_dialplan(destination_number);
	    return;
	end
end             
end
----------------------- END CALLING CARD SECTION -------------------------------

------------------------- VOICEMAIL LISTEN START--------------------------------------
if(tonumber(config['voicemail_number']) == tonumber(destination_number)) then
Logger.info("[Dialplan] VOICEMAIL : ")
	xml = xml_voicemail(xml,destination_number)
return;
end
------------------------- VOICEMAIL LISTEN END --------------------------------------

---- Getting callerid for caller id translation feature ---- 
if (params:getHeader('variable_effective_caller_id_number') ~= nil) then
    callerid_number = params:getHeader('variable_effective_caller_id_number')
    callerid_name = params:getHeader('variable_effective_caller_id_name')
else
    callerid_number = params:getHeader('Caller-Caller-ID-Number')
    callerid_name = params:getHeader('Caller-Caller-ID-Name')
end       

if(config['opensips'] == '0') then
    if (params:getHeader('variable_sip_h_P-effective_caller_id_name') ~= nil) then 
        callerid_name = params:getHeader('variable_sip_h_P-effective_caller_id_name')
    else
        callerid_name = ''
    end

    if (params:getHeader('variable_sip_h_P-effective_caller_id_number') ~= nil) then 
        callerid_number = params:getHeader('variable_sip_h_P-effective_caller_id_number')
    else
        callerid_number = ''
    end
end   	 
--To override custom callerid
if custom_callerid then custom_callerid() end     
Logger.info("[Dialplan] Caller Id name / number  : "..callerid_name.." / "..callerid_number)

--Saving caller id information in array
callerid_array = {}
callerid_array['cid_name'] = callerid_name
callerid_array['cid_number'] = callerid_number
callerid_array['original_cid_name'] = callerid_name
callerid_array['original_cid_number'] = callerid_number
--------------------------------------

-- Define default variables 
local call_direction = 'outbound'
local calltype = 'ASTPP-STANDARD'
local accountcode = ''
local sipcall = ''
local auth_type = 'default'
local authinfo = {}
local accountname = 'default'

-- Check call type 

accountcode = params:getHeader("variable_accountcode")
--To override custom calltype
if custom_calltype then
	calltype_custom = custom_calltype() 
	if(calltype_custom ~= '' and calltype_custom ~= nil) then
		calltype = calltype_custom 
	end
end
--To override custom accountcode
if custom_accountcode then
	accountcode_custom = custom_accountcode()
	if(accountcode_custom ~= '' and accountcode_custom ~= nil) then
		accountcode = accountcode_custom 
	end
end
sipcall = params:getHeader("variable_sipcall")

call_direction = define_call_direction(destination_number,accountcode,config)
Logger.info("[Dialplan] Call direction : ".. call_direction)

--IF opensips then check then get account code from $params->{'variable_sip_h_P-Accountcode'}
if(config['opensips']=='0' and params:getHeader('variable_sip_h_P-Accountcode') ~= '' and params:getHeader('variable_sip_h_P-Accountcode') ~= nil and params:getHeader("variable_accountcode") == nil and params:getHeader('variable_sip_h_P-Accountcode') ~= '<null>')
then
	accountcode = params:getHeader('variable_sip_h_P-Accountcode');
end

-- If we are on the xconnect profile, trust X-Astpp-Account if it exists.
if (params:getHeader('variable_sofia_profile_name') == 'xconnect' ) then
	accountcode = params:getHeader('variable_sip_h_X-Astpp-Account')
end

-- If no account code found then do further authentication of call
if (accountcode == nil or accountcode == '') then

    from_ip = ""	
    if(config['opensips']=='0') then
    	from_ip = params:getHeader("variable_sip_h_X-AUTH-IP")
    else
    	from_ip = params:getHeader('Hunt-Network-Addr')
    end	

    authinfo = doauthentication(destination_number,from_ip)

    if (authinfo ~= nil and authinfo['type'] == 'acl') then      
    	accountcode = authinfo['account_code']
        if (authinfo['prefix'] ~= '') then
            destination_number = do_number_translation(authinfo['prefix'].."/*",destination_number)
        end
    	auth_type = 'acl';
    	accountname = authinfo['name'] or ""
    end
end

-- Still no account code that means call is not authenticated.
if (accountcode == nil or accountcode == "") then
  Logger.notice("[Dialplan] Call authentication fail..!!"..config['playback_audio_notification'])
  error_xml_without_cdr(destination_number,"AUTHENTICATION_FAIL",calltype,config['playback_audio_notification'],'0') 
  return
end

Logger.notice("[Accountcode : ".. accountcode .."]" );

--Destination number string 
number_loop_str = number_loop(destination_number,'blocked_patterns') 

-- Do authorization
userinfo = doauthorization(accountcode,call_direction,destination_number,number_loop_str)

if(userinfo ~= nil) then

	if(userinfo['ACCOUNT_ERROR'] == 'DESTINATION_BLOCKED') then
		error_xml_without_cdr(destination_number,"DESTINATION_BLOCKED",calltype,config['playback_audio_notification'],userinfo['id'])
		return 0
	end
    
    if(userinfo['ACCOUNT_ERROR'] == 'ACCOUNT_INACTIVE_DELETED') then
		error_xml_without_cdr(destination_number,"ACCOUNT_INACTIVE_DELETED",calltype,config['playback_audio_notification'],'0')
		return 0
	end

    -- Get package information of customer
	package_array = package_calculation (destination_number,userinfo,call_direction)
		
	userinfo = package_array[1]
	package_maxlength = package_array[2] or ""
    -------

	if(userinfo['ACCOUNT_ERROR'] == 'NO_SUFFICIENT_FUND') then
		error_xml_without_cdr(destination_number,"NO_SUFFICIENT_FUND",calltype,config['playback_audio_notification'],userinfo['id'])
		return 0
	end

	if(userinfo['local_call'] == '1' and call_direction == "local") then
        Logger.warning("[Functions] [DOAUTHORIZATION] ["..accountcode.."] LOCAL CALL IS DISABLE....!!");
		call_direction = 'outbound'
	end
end

--------------------------------------- SPEED DIAL --------------------------------------
if(string.len(destination_number) == 1 ) then
	destination_number = get_speeddial_number(destination_number,userinfo['id'])
	Logger.info("[Dialplan] SPEED DIAL NUMBER : "..destination_number)
    
    -- Overriding call direction if speed dial destination is for DID or local extension 
    call_direction = define_call_direction(destination_number,accountcode,config)
    Logger.info("[Dialplan] New Call direction : ".. call_direction)
end
-----------------------------------------------------------------------------------------


if (userinfo ~= nil) then  

	if (config['realtime_billing'] == "0") then
		local nibble_id = ""
		local nibble_rate = ""
		local nibble_connect_cost = ""
		local nibble_init_inc = ""
		local nibble_inc = ""
	end
    
	-- print customer information 
	Logger.info("=============== Account Information ===================")
	Logger.info("User id : "..userinfo['id'])  
	Logger.info("Account code : "..userinfo['number'])
	Logger.info("Balance : "..get_balance(userinfo))  
	Logger.info("Type : "..userinfo['posttoexternal'].." [0:prepaid,1:postpaid]")  
	Logger.info("Ratecard id : "..userinfo['pricelist_id'])  
	Logger.info("========================================================")    
    

	-- If call is pstn and dialed modify defined then do number translation
	if (call_direction == 'outbound' and userinfo['dialed_modify'] ~= '') then
		destination_number = do_number_translation(userinfo['dialed_modify'],destination_number)
	end

     -- If call is pstn and caller id translation defined then do caller id translation 
	if (userinfo['std_cid_translation'] ~= '') then
        Logger.info("[DIALPLAN] Caller ID Translation Starts")
		callerid_array['cid_name'] = do_number_translation(userinfo['std_cid_translation'],callerid_array['cid_name'])
		callerid_array['cid_number'] = do_number_translation(userinfo['std_cid_translation'],callerid_array['cid_number'])
        Logger.info("[DIALPLAN] Caller ID Translation Ends")
	end

	if(call_direction == 'inbound'  and config['did_global_translation'] ~= '') then
		destination_number = do_number_translation(config['did_global_translation'],destination_number)
	end     

  	number_loop_str = number_loop(destination_number)

	-- Fine max length of call based on origination rates.
	origination_array = get_call_maxlength(userinfo,destination_number,call_direction,number_loop_str,config,didinfo)
	    
	if( origination_array == 'NO_SUFFICIENT_FUND' or origination_array == 'ORIGNATION_RATE_NOT_FOUND') then
	    error_xml_without_cdr(destination_number,origination_array,calltype,config['playback_audio_notification'],userinfo['id']) 
	    return
	end
	
	maxlength = origination_array[1]
	user_rates = origination_array[2]
	xml_user_rates = origination_array[3] or ""
	
	if (config['realtime_billing'] == "0") then		
		nibble_id = userinfo['id']
		nibble_rate = user_rates['cost']
		nibble_connect_cost = user_rates['connectcost']
	    nibble_init_inc = user_rates['init_inc']
	    nibble_inc = user_rates['inc']
	end	

	-- If customer has free seconds then override max length variable with it. 
	if(package_maxlength ~= "") then	
		maxlength=package_maxlength
	end   
    
    -- Reseller validation starts
	local reseller_ids = {}
	local i = 1
    local reseller_cc_limit = ""
    
	-- Set customer information in new variable
	customer_userinfo = userinfo
	rate_carrier_id = user_rates['trunk_id']
    
	while (tonumber(userinfo['reseller_id']) > 0 and tonumber(maxlength) > 0 ) do 
		number_loop_str = number_loop(destination_number,'blocked_patterns') 
		Logger.notice("FINDING LIMIT FOR RESELLER: "..userinfo['reseller_id'])

		reseller_userinfo = doauthorization(userinfo['reseller_id'],call_direction,destination_number,number_loop_str)
         
        if(reseller_userinfo['ACCOUNT_ERROR'] == 'ACCOUNT_INACTIVE_DELETED') then
		    -- error_xml_without_cdr(destination_number,"ACCOUNT_INACTIVE_DELETED",calltype,config['playback_audio_notification'],userinfo)
		    return 0
	    end

        -- Get package information of reseller
	    package_array = package_calculation (destination_number,reseller_userinfo,call_direction)
		
	    reseller_userinfo = package_array[1]
	    package_maxlength = package_array[2] or ""
        -------

	    if(reseller_userinfo['ACCOUNT_ERROR'] == 'NO_SUFFICIENT_FUND') then
		    error_xml_without_cdr(destination_number,"NO_SUFFICIENT_FUND",calltype,config['playback_audio_notification'],reseller_userinfo['id'])
		    return 0
	    end

----
    
	    -- If call is pstn and dialed modify defined then do number translation
		if (call_direction == 'outbound' and reseller_userinfo['dialed_modify'] ~= '') then
			destination_number = do_number_translation(reseller_userinfo['dialed_modify'],destination_number)
		end    
			number_loop_str = number_loop(destination_number)
			reseller_ids[i] = reseller_userinfo
		    
			-- print reseller information 
			Logger.info("=============== Reseller Information ===================")
			Logger.info("User id : "..reseller_userinfo['id'])  
			Logger.info("Account code : "..reseller_userinfo['number'])
			Logger.info("Balance : "..get_balance(reseller_userinfo))  
			Logger.info("Type : "..reseller_userinfo['posttoexternal'].." [0:prepaid,1:postpaid]")  
			Logger.info("Ratecard id : "..reseller_userinfo['pricelist_id'])  
			
			origination_array_reseller=get_call_maxlength(reseller_userinfo,destination_number,call_direction,number_loop_str,config,didinfo)

            if( origination_array_reseller == 'NO_SUFFICIENT_FUND' or origination_array_reseller == 'ORIGNATION_RATE_NOT_FOUND') then
	            error_xml_without_cdr(destination_number,origination_array_reseller,calltype,1,reseller_userinfo['id']) 
        	    return
        	end 

			reseller_maxlength = origination_array_reseller[1];
			reseller_rates = origination_array_reseller[2];
			xml_reseller_rates = origination_array_reseller[3];
			
			if (config['realtime_billing'] == "0") then
				-------------NIBBLE BILLING PARAM SET STARTS----------------------------
				nibble_id = nibble_id..","..reseller_userinfo['id']
				nibble_rate = nibble_rate..","..reseller_rates['cost']
				nibble_connect_cost = nibble_connect_cost..","..reseller_rates['connectcost']
    			nibble_init_inc = nibble_init_inc..","..reseller_rates['init_inc']
    			nibble_inc = nibble_inc..","..reseller_rates['inc']
    			-------------NIBBLE BILLING PARAM SET ENDS----------------------------
			end

			xml_user_rates = xml_user_rates.."||"..xml_reseller_rates
			Logger.info("Reseller xml_user_rates : "..xml_user_rates)  
			Logger.info("========================================================")  
			--.."|RTI"..reseller_userinfo['pricelist_id'].."|UID"..reseller_userinfo['id']
			--if (reseller_maxlength <= '0') then
				-- Logger.notice("[Dialplan] Reseller max length of call not found!!!");
				--  return
			--end

            -- If reseller has free seconds then override max length variable with it. 
	        if(package_maxlength ~= "") then	
		        xml_reseller_rates=package_maxlength
	        end  

			if (tonumber(reseller_maxlength) < tonumber(maxlength)) then 
				maxlength = reseller_maxlength
			end

            -- ITPL : Added checkout for reseller concurrent calls.    
            if (tonumber(reseller_userinfo['maxchannels']) > 0 or tonumber(reseller_userinfo['cps']) > 0) then
                reseller_cc_limit = set_cc_limit_resellers(reseller_userinfo)
            end

			if (tonumber(reseller_maxlength) < 1 or tonumber(reseller_rates['cost']) > tonumber(user_rates['cost'])) then
				error_xml_without_cdr(destination_number,"RESELLER_COST_CHEAP",calltype,1,customer_userinfo['id']); 
				Logger.info("Reseller cost : "..reseller_rates['cost'].." User cost : "..user_rates['cost'])
		    	Logger.error("[Dialplan] Reseller call price is cheaper, so we cannot allow call to process!!")
				return
	    	end

			rate_carrier_id = reseller_rates['trunk_id']
			userinfo = reseller_userinfo
	end -- End while 
    
	if (config['realtime_billing'] == "0") then
    		Logger.info("NIBBLE ID "..nibble_id)
    		Logger.info("NIBBLE RATE "..nibble_rate)
    		Logger.info("NIBBLE CONNECT COST "..nibble_connect_cost)
    		Logger.info("NIBBLE INITIAL INC "..nibble_init_inc)
    		Logger.info("NIBBLE INC "..nibble_inc)
	    
			customer_userinfo["nibble_accounts"] = nibble_id
	    	customer_userinfo["nibble_rates"] = nibble_rate
	    	customer_userinfo["nibble_connect_cost"] = nibble_connect_cost
	    	customer_userinfo["nibble_init_inc"] = nibble_init_inc
	    	customer_userinfo["nibble_inc"] = nibble_inc
	end
	
	--- Reseller validation ends
	if ( tonumber(maxlength) <= 0 ) then
	    error_xml_without_cdr(destination_number,"NO_SUFFICIENT_FUND",calltype,config['playback_audio_notification'],customer_userinfo['id']);
	end


	Logger.info("Call Max length duration : "..maxlength.." minutes")
	--say_timelimit(minutes) 
	local xml = {}
    
	
	-- Generate dialplan for call
	if (call_direction == 'inbound') then
		
		-- ********* Check RECEIVER Balance and status of the Account *************
		local dialuserinfo

        if (INB_FREE ~= nil) then        
            config['free_inbound'] = true
        end

		dialuserinfo = doauthorization(didinfo['accountid'],call_direction,destination_number,number_loop)	
		-- ********* Check & get Dialer Rate card information *********
			origination_array_DID = get_call_maxlength(customer_userinfo,destination_number,"outbound",number_loop_str,config)
			local actual_userinfo = customer_userinfo
			 Logger.info("[userinfo] Actual CustomerInfo XML:" .. actual_userinfo['id'])
			--customer_userinfo['id'] = didinfo['accountid'];
			if(origination_array_DID ~= 'ORIGNATION_RATE_NOT_FOUND' and origination_array_DID ~= 'NO_SUFFICIENT_FUND') then 
				Logger.info("[userinfo] Userinfo XML:" .. customer_userinfo['id']) 
				xml_did_rates = origination_array_DID[3]
			else
				error_xml_without_cdr(destination_number,"ORIGNATION_RATE_NOT_FOUND",calltype,config['playback_audio_notification'],customer_userinfo['id'])
				return
			end
		-- ********* END *********
		while (tonumber(customer_userinfo['reseller_id']) > 0  ) do 
			Logger.info("[WHILE DID CONDITION] FOR CHECKING RESELLER :" .. customer_userinfo['reseller_id']) 
			customer_userinfo = doauthorization(customer_userinfo['reseller_id'],call_direction,destination_number,number_loop)	
			origination_array_DID = get_call_maxlength(customer_userinfo,destination_number,"outbound",number_loop_str,config)

			if(origination_array_DID ~= 'ORIGNATION_RATE_NOT_FOUND' and origination_array_DID ~= 'NO_SUFFICIENT_FUND') then 
				Logger.info("[userinfo] Userinfo XML:" .. customer_userinfo['id']) 
				xml_did_rates = xml_did_rates .."||"..origination_array_DID[3]
			else
				error_xml_without_cdr(destination_number,"ORIGNATION_RATE_NOT_FOUND",calltype,config['playback_audio_notification'],customer_userinfo['id'])
				return
			end
		end
		-- ********* END *********
		 Logger.info("[userinfo] Actual CustomerInfo XML : " .. actual_userinfo['id'])
xml = freeswitch_xml_header(xml,destination_number,accountcode,maxlength,call_direction,accountname,xml_user_rates,actual_userinfo,config,xml_did_rates,nil,callerid_array)

		xml = freeswitch_xml_inbound(xml,didinfo,actual_userinfo,config,xml_did_rates,callerid_array)
		xml = freeswitch_xml_footer(xml)	   	    
		XML_STRING = table.concat(xml, "\n");
		Logger.debug("[Dialplan] Generated XML:" .. XML_STRING)  

	elseif (call_direction == 'local') then
		local SipDestinationInfo;
		SipDestinationInfo = check_local_call(destination_number)
		
		xml = freeswitch_xml_header(xml,destination_number,accountcode,maxlength,call_direction,accountname,xml_user_rates,customer_userinfo,config,nil,nil,callerid_array)

		xml = freeswitch_xml_local(xml,destination_number,SipDestinationInfo,callerid_array)
		xml = freeswitch_xml_footer(xml)	   	    
		XML_STRING = table.concat(xml, "\n");
		Logger.debug("[Dialplan] Generated XML:\n" .. XML_STRING)  

	else
		-- Get termination rates 
		termination_rates = get_carrier_rates (destination_number,number_loop_str,userinfo['pricelist_id'],rate_carrier_id,user_rates['routing_type'])
	
	if (termination_rates ~= nil) then
	    local i = 1
	    local carrier_array = {}
	    for termination_key,termination_value in pairs(termination_rates) do
		if ( tonumber(termination_value['cost']) > tonumber(user_rates['cost']) ) then		    
		    	Logger.notice(termination_value['path']..": "..termination_value['cost'] .." > "..user_rates['cost']..", skipping")  
		else
			Logger.info("=============== Termination Rates Information ===================")
			Logger.info("ID : "..termination_value['outbound_route_id'])  
			Logger.info("Code : "..termination_value['pattern'])  
			Logger.info("Destination : "..termination_value['comment'])  
			Logger.info("Connectcost : "..termination_value['connectcost'])  
			Logger.info("Free Seconds : "..termination_value['includedseconds'])  
			Logger.info("Prefix : "..termination_value['pattern'])      		    
			Logger.info("Strip : "..termination_value['strip'])      		  
			Logger.info("Prepend : "..termination_value['prepend'])      		  
			Logger.info("Carrier id : "..termination_value['trunk_id'])  		      		    
			Logger.info("carrier_name : "..termination_value['path'])
			Logger.info("dialplan_variable : "..termination_value['dialplan_variable']) 
			Logger.info("Failover gateway : "..termination_value['path1'])      		    
			Logger.info("Vendor id : "..termination_value['provider_id'])      		    		    
			Logger.info("Number Translation : "..termination_value['dialed_modify'])      		    		    		    
			Logger.info("Max channels : "..termination_value['maxchannels'])	    
			Logger.info("========================END OF TERMINATION RATES=======================")
			carrier_array[i] = termination_value
			i = i+1
		end
	    end -- For EACH END HERE
	    
		-- If we get any valid carrier rates then build dialplan for outbound call
		if (i > 1) then

            callerid = get_override_callerid(customer_userinfo,callerid_name,callerid_number)
            if (callerid['cid_name'] ~= nil) then
                callerid_array['cid_name'] = callerid['cid_name']
                callerid_array['cid_number'] = callerid['cid_number']
                callerid_array['original_cid_name'] = callerid['cid_name']
                callerid_array['original_cid_number'] = callerid['cid_number']
            end 
			
			xml = freeswitch_xml_header(xml,destination_number,accountcode,maxlength,call_direction,accountname,xml_user_rates,customer_userinfo,config,nil,reseller_cc_limit,callerid_array)

			-- Added code to override callerid
            --xml = override_callerid_management(xml,customer_userinfo)

			for carrier_arr_key,carrier_arr_array in pairs(carrier_array) do
			    xml = freeswitch_xml_outbound(xml,destination_number,carrier_arr_array,callerid_array)
			end

			xml = freeswitch_xml_footer(xml)
		else
			-- If no route found for outbound call then send no result dialplan for further process in fs
			Logger.notice("[Dialplan] No termination rates found...!!!");
			error_xml_without_cdr(destination_number,"TERMINATION_RATE_NOT_FOUND",calltype,config['playback_audio_notification'],customer_userinfo['id']) 
			return
		end  --- IF ELSE END HERE
		XML_STRING = table.concat(xml, "\n");
		Logger.debug("[Dialplan] Generated XML:\n" .. XML_STRING)  
	else
		Logger.notice("[Dialplan] No termination rates found...!!!");
		error_xml_without_cdr(destination_number,"TERMINATION_RATE_NOT_FOUND",calltype,config['playback_audio_notification'],customer_userinfo['id']);
		return
	end
    end
else
		error_xml_without_cdr(destination_number,"ACCOUNT_INACTIVE_DELETED",calltype,config['playback_audio_notification'],customer_userinfo['id']);
		return
end
