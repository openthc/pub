--
-- PostgreSQL database dump
--

\c openthc_pub

SET check_function_bodies = false;
SET client_encoding = 'UTF8';
SET client_min_messages = warning;
SET default_table_access_method = heap;
SET default_tablespace = '';
SET default_with_oids = false;
SET idle_in_transaction_session_timeout = 0;
SET lock_timeout = 0;
SET row_security = off;
SET search_path TO public;
SET standard_conforming_strings = on;
SET statement_timeout = 0;
SET xmloption = content;

--
-- Name: message; Type: TABLE; Schema: public; Owner: openthc_pub
--

CREATE TABLE public.message (
    id character varying(256) NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone,
    deleted_at timestamp with time zone,
    expires_at timestamp with time zone,
    size integer,
    name text,
    type text,
    body bytea
);


ALTER TABLE public.message OWNER TO openthc_pub;

--
-- Name: profile; Type: TABLE; Schema: public; Owner: openthc_pub
--

CREATE TABLE public.profile (
    id character varying(64) NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone,
    deleted_at timestamp with time zone,
    name text,
    meta jsonb
);


ALTER TABLE public.profile OWNER TO openthc_pub;

--
-- Name: message message_pkey; Type: CONSTRAINT; Schema: public; Owner: openthc_pub
--

ALTER TABLE ONLY public.message
    ADD CONSTRAINT message_pkey PRIMARY KEY (id);


--
-- Name: profile profile_pkey; Type: CONSTRAINT; Schema: public; Owner: openthc_pub
--

ALTER TABLE ONLY public.profile
    ADD CONSTRAINT profile_pkey PRIMARY KEY (id);


--
-- PostgreSQL database dump complete
--
