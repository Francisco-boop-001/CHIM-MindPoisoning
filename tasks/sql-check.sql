-- Lead verification: read-only planning only. Never add ANALYZE or execute the prepared mutations.
SET default_transaction_read_only = on;
SHOW transaction_read_only;

EXPLAIN SELECT profile.id,
(SELECT player.value FROM core_player player WHERE player.id='player_name' LIMIT 1) AS player_name
FROM chim_meta.playthrough_profiles profile WHERE profile.is_active IS TRUE ORDER BY profile.id LIMIT 2;

PREPARE mp_ack(text) AS
SELECT COALESCE(jsonb_agg(to_jsonb(e) ORDER BY e.rowid), '[]'::jsonb) AS events
FROM (SELECT rowid,type,utterance_id,delivery_state,gamets,data FROM eventlog
      WHERE type='chat' AND utterance_id=$1 ORDER BY rowid LIMIT 2) e;
EXPLAIN EXECUTE mp_ack('utt_plugin_contract_check');

PREPARE mp_event(bigint,text) AS
SELECT e.rowid,e.type,e.utterance_id,e.delivery_state,e.gamets,e.data FROM eventlog e
WHERE e.rowid=$1 AND e.type='chat' AND e.utterance_id=$2
AND (SELECT count(*) FROM eventlog exact_event WHERE exact_event.type='chat' AND exact_event.utterance_id=$2)=1
FOR SHARE OF e;
EXPLAIN EXECUTE mp_event(-1,'utt_plugin_contract_check');

PREPARE mp_write(integer,text,jsonb,jsonb,numeric) AS
UPDATE core_npc_master
SET extended_data=jsonb_set(COALESCE(jsonb_set(COALESCE(extended_data,'{}'::jsonb),'{relationships}',COALESCE(extended_data->'relationships','{}'::jsonb),true),'{}'::jsonb),ARRAY['relationships',$2::text],$3::jsonb,true),
plugin_extended_data=jsonb_set(COALESCE(plugin_extended_data,'{}'::jsonb),ARRAY['mind_poisoning']::text[],$4::jsonb,true),
gamets_last_updated=$5 WHERE id=$1 RETURNING id;
EXPLAIN EXECUTE mp_write(-1,'Player','{"aff":1,"type":"neutral"}','{"playthrough_id":"1","floor_event_id":0,"events":[]}',1);

PREPARE mp_history(integer,jsonb,jsonb,numeric,integer) AS
SELECT npc_id,(extended_data=$2::jsonb) AS extended_matches,
(plugin_extended_data=$3::jsonb) AS plugins_match,(gamets_last_updated=$4::numeric) AS gamets_match
FROM core_npc_master_history WHERE history_id=$1 AND npc_id=$5;
EXPLAIN EXECUTE mp_history(-1,'{}','{}',1,-1);

-- Plan the actual full-row history column mapping; no INSERT is executed.
SELECT 'EXPLAIN INSERT INTO core_npc_master_history (' ||
string_agg(quote_ident(CASE WHEN column_name='id' THEN 'npc_id' ELSE column_name END),',' ORDER BY ordinal_position) ||
',created) SELECT ' || string_agg(quote_ident(column_name),',' ORDER BY ordinal_position) ||
',now() FROM core_npc_master WHERE id=-1 RETURNING history_id'
FROM information_schema.columns WHERE table_schema='public' AND table_name='core_npc_master'
\gexec

-- Exercise the missing-parent fix only on a literal, preserving an unrelated empty object.
SELECT jsonb_set(jsonb_set('{"unrelated":{}}'::jsonb,'{relationships}','{}'::jsonb,true),
ARRAY['relationships','Player'],'{"aff":1,"type":"neutral"}'::jsonb,true)
= '{"unrelated":{},"relationships":{"Player":{"aff":1,"type":"neutral"}}}'::jsonb AS parent_and_object_preserved;
